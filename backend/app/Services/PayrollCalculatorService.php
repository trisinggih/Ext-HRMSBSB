<?php

namespace App\Services;

use App\Models\BpjsSetting;
use App\Models\Employee;
use App\Models\EmployeePayrollSetting;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\PtkpSetting;
use App\Models\TaxBracket;
use App\Models\TaxRule;
use App\Models\TaxEmployeeSetting;
use App\Models\EmployeeSalaryComponent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * PayrollCalculatorService
 *
 * SEMUA formula bernilai konfigurasi dibaca dari TABEL:
 *  - komponen gaji         : payroll_components + employee_salary_components
 *  - tarif BPJS            : bpjs_settings (employee_rate/company_rate, max_limit)
 *  - bracket PPh21/PKP     : tax_rules + tax_brackets
 *  - PTKP per status       : ptkp_settings
 *  - nilai tetap lain      : payroll_settings (biaya jabatan, cap, dsb)
 *
 * Formula yang boleh "tetap" hanya operasi aritmatika dasar (penjumlahan/persentase),
 * nilainya selalu berasal dari config tabel di atas, bukan dari konstanta kode.
 */
class PayrollCalculatorService
{
    /** pembulatan ke 2 desimal untuk semua nominal rupiah */
    private function money(float $value): float
    {
        return round($value, 2);
    }

    /**
     * Ambil komponen salary aktif milik employee pada tanggal tertentu.
     * Gabungkan master payroll_components dengan employee_salary_components.
     */
    private function resolveEmployeeComponents(Employee $employee, \App\Models\EmployeeAssignment $assignment, Carbon $date, array &$variables, bool $isBonusOnly = false, ?PayrollPeriod $period = null)
    {
        $empRows = EmployeeSalaryComponent::where('employee_id', $employee->id)
            ->where(function($q) use ($assignment) {
                $q->whereNull('employee_assignment_id')
                  ->orWhere('employee_assignment_id', $assignment->id);
            })
            ->where('is_active', true)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', $date->toDateString());
            })
            ->with('component')
            ->get();

        // Hanya gunakan komponen yang aktif & di-assign ke karyawan (tidak auto-pull dari posisi)
        $mergedRows = collect($empRows);

        // === FALLBACK: jika tidak ada data di employee_salary_components untuk assignment ini,
        // coba baca dari pivot table (employee_assignment_payroll_components)
        // Ini terjadi saat MONTHLY_PAID_MONTHLY pertama kali diset dan belum ada amount yang disimpan
        if ($assignment->id && $mergedRows->isEmpty()) {
            $pivotComponents = $assignment->payrollComponents()->get();
            if ($pivotComponents->isNotEmpty()) {
                $fallbackRows = $pivotComponents->map(function($comp) use ($employee, $assignment, $date) {
                    // Cari amount dari employee_salary_components jika ada (tanpa filter assignment)
                    $existingRow = EmployeeSalaryComponent::where('employee_id', $employee->id)
                        ->where('payroll_component_id', $comp->id)
                        ->where('is_active', true)
                        ->first();

                    // Buat pseudo-row (stdClass) agar kompatibel dengan logika di bawah
                    $row = new \stdClass();
                    $row->id = null;
                    $row->employee_id = $employee->id;
                    $row->payroll_component_id = $comp->id;
                    $row->employee_assignment_id = $assignment->id;
                    $row->amount = $existingRow ? (float)$existingRow->amount : 0;
                    $row->calculation_type = 'fixed';
                    $row->is_active = true;
                    $row->effective_from = $date->toDateString();
                    $row->component = $comp;
                    return $row;
                });
                $mergedRows = $fallbackRows;
            }
        }

        // Filter berdasarkan payment_schema assignment
        $salaryType = $assignment->salary_cycle_type ?? null;
        if ($salaryType) {
            $mergedRows = $mergedRows->filter(function($row) use ($salaryType) {
                $comp = $row->component;
                if (!$comp) return false;
                $schema = $comp->payment_schema;
                // null = belum dikonfigurasi, berlaku untuk semua schema
                if ($schema === null) return true;
                // [] kosong = sengaja dikosongkan, tidak berlaku untuk skema apapun
                if (is_array($schema) && count($schema) === 0) return false;
                return in_array($salaryType, (array) $schema);
            });
        }

        $out = [];
        $tempVariables = $variables;

        // Helper: periksa apakah komponen perlu dilewati
        $shouldSkip = function($comp) use ($isBonusOnly, $date, $period, $assignment) {
            if (!$comp || !$comp->is_active) return true;
            if ($comp->effective_from && $comp->effective_from->gt($date)) return true;
            if ($isBonusOnly && !$comp->is_separate_payslip) return true;
            if (!$isBonusOnly && $comp->is_separate_payslip) return true;

            if (isset($period) && $period->cycle_type) {
                $cycleType = $period->cycle_type;
                
                // For DAILY_PAID_DAILY, if cycle_type is daily_payment, skip non-daily components
                if ($assignment->salary_cycle_type === 'DAILY_PAID_DAILY' && $cycleType === 'daily_payment') {
                    $code = strtolower($comp->code);
                    $isHarianOnly = in_array($code, ['gaji_harian', 'gaji_harian_per_bulan', 'uang_lembur_harian', 'potongan_terlambat_harian', 'potongan_absen_harian']);
                    if (!$isHarianOnly) return true; // skip!
                }

                // Split into weekly_payment/weekly_payslip when period cycle_type is weekly_payment or weekly_payslip
                if (in_array($cycleType, ['weekly_payment', 'weekly_payslip']) || $assignment->salary_cycle_type === 'DAILY_PAID_WEEKLY') {
                    $code = strtolower($comp->code);
                    $isTunjangan = str_contains($code, 'tunjangan') || in_array($code, ['kerajinan', 'gaji_bulanan', 'gaji_bulanan_dasar']);
                    // Deductions like bpjs, pph21, kasbon, potongan_terlambat, potongan_absen are processed on the monthly payslip
                    $isMonthlyDeduction = in_array($comp->type, ['tax', 'bpjs']) || in_array($code, ['potongan_kasbon', 'potongan_terlambat', 'potongan_absen']);
                    
                    $isMonthlySlipComponent = $isTunjangan || $isMonthlyDeduction;

                    if ($cycleType === 'weekly_payment' && $isMonthlySlipComponent) {
                        return true; // Skip allowances/monthly deductions in weekly payment (Step 1)
                    }
                    if ($cycleType === 'weekly_payslip' && !$isMonthlySlipComponent) {
                        return true; // Skip base wages/daily attendance in monthly slip (Step 2)
                    }
                }
            }

            return false;
        };

        // Pre-inject: Masukkan amount mentah komponen FIXED ke $tempVariables
        // Supaya formula antar-komponen (mis: {gaji_harian} dalam gaji_harian_per_bulan) bisa diselesaikan
        foreach ($mergedRows as $row) {
            $comp = $row->component;
            if ($shouldSkip($comp)) continue;

            // Hanya inject komponen dengan calculation_type fixed dan ada nilai
            $calcType = $row->calculation_type ?? 'fixed';
            if ($calcType === 'fixed' && $comp->code) {
                $rawAmount = (float) ($row->amount ?? 0);
                if ($rawAmount > 0) {
                    $tempVariables[$comp->code] = $rawAmount;
                }
            }
        }

        // Pass 1: Hitung semua komponen yang is_bpjs_basis untuk menentukan bpjs_basis_total
        $bpjsBasisTotal = 0;
        foreach ($mergedRows as $row) {
            $comp = $row->component;
            if ($shouldSkip($comp)) continue;

            if ($comp->is_bpjs_basis) {
                $calcData = $this->componentAmount($comp, $row, $tempVariables);
                $bpjsBasisTotal += (float) $calcData['amount'];
            }
        }
        
        if ($bpjsBasisTotal == 0) {
            $bpjsBasisTotal = $variables['basic_salary'] ?? 0;
        }
        $tempVariables['bpjs_basis_total'] = $bpjsBasisTotal;

        // Pass 1.5: Hitung semua komponen pendapatan yang is_taxable untuk menentukan taxable_earnings
        $taxableEarnings = 0;
        foreach ($mergedRows as $row) {
            $comp = $row->component;
            if ($shouldSkip($comp)) continue;

            if ($comp->type === 'earning' && $comp->is_taxable) {
                $calcData = $this->componentAmount($comp, $row, $tempVariables);
                $taxableEarnings += (float) $calcData['amount'];
            }
        }
        $tempVariables['taxable_earnings'] = $taxableEarnings;

        // Tentukan Kategori TER berdasarkan PTKP
        $ptkp = $employee->ptkp_status;
        $terCategory = null;
        if (in_array($ptkp, ['TK/0', 'TK/1', 'K/0'])) $terCategory = 'A';
        elseif (in_array($ptkp, ['TK/2', 'TK/3', 'K/1', 'K/2'])) $terCategory = 'B';
        elseif ($ptkp === 'K/3') $terCategory = 'C';

        if ($terCategory) {
            $terRate = \App\Models\TaxTerRate::where('category', $terCategory)
                ->where('min_bruto', '<=', $taxableEarnings)
                ->where(function($q) use ($taxableEarnings) {
                    $q->whereNull('max_bruto')
                      ->orWhere('max_bruto', '>=', $taxableEarnings);
                })
                ->where('is_active', true)
                ->orderBy('min_bruto', 'desc')
                ->first();

            $tempVariables['tax_rate'] = $terRate ? ($terRate->rate / 100) : 0;
            $tempVariables['ter_category'] = $terCategory;
            $tempVariables['ptkp_status'] = $ptkp;
        } else {
            $tempVariables['tax_rate'] = 0;
            $tempVariables['ter_category'] = 'N/A';
            $tempVariables['ptkp_status'] = $ptkp ?: 'N/A';
        }
        $tempVariables['tax_deduction'] = 0; // TER has no fixed deduction

        // Pass 2: Hitung SEMUA komponen dengan variabel lengkap
        foreach ($mergedRows as $row) {
            $comp = $row->component;
            if ($shouldSkip($comp)) continue;

            // Komponen show_on_payslip=false adalah basis kalkulasi saja, tidak masuk ke total/slip
            if ($comp->show_on_payslip === false) continue;

            // Uang lembur tidak boleh tampil langsung, hanya tampil jika ada pengajuan lembur yang di-approve
            $isOvertimeComp = str_contains(strtolower($comp->code), 'lembur') || str_contains(strtolower($comp->code), 'overtime');
            if ($isOvertimeComp && (float)($tempVariables['total_overtime_minutes'] ?? 0) <= 0) {
                continue;
            }

            $calcData = $this->componentAmount($comp, $row, $tempVariables);
            $out[] = [
                'payroll_component_id' => $comp->id,
                'label' => $comp->name,
                'code' => $comp->code,
                'type' => $comp->type,
                'is_taxable' => (bool) $comp->is_taxable,
                'is_bpjs_basis' => (bool) $comp->is_bpjs_basis,
                'amount' => $calcData['amount'],
                'formula' => $calcData['formula'],
                'matched_variables' => $calcData['matched_variables'] ?? [],
            ];
        }

        // Export the temp variables back to the referenced array
        $variables = $tempVariables;

        return $out;
    }

        /**
     * Hitung nominal sebuah komponen.
     *  - fixed    : pakai amount (employee salary component atau master)
     *  - percentage : percentage% dari gaji pokok
     *  - formula  : evaluasi ekspresi sederhana (operasi tambah/kurang/kali/bagi persen terhadap Gajipokok)
     * Nilai persentase & variabel selalu dari config tabel.
     */
    private function componentAmount(PayrollComponent $comp, $row, array $variables): array
    {
        $basic = (float) ($variables['basic_salary'] ?? 0);
        $matchedVars = [];
        $code = strtolower($comp->code);

        $attendDays = (float) ($variables['total_attendance_days'] ?? 0);
        $monthDays = (float) ($variables['total_days_in_month'] ?? $variables['total_period_days'] ?? 30);
        if ($monthDays <= 0) {
            $monthDays = 30;
        }
        $missingDays = max(0, $monthDays - $attendDays);
        $unpaid = (float) ($variables['total_unpaid_leave_days'] ?? 0);
        $totalLiburEffectif = max($missingDays, $unpaid);

        // Aturan Khusus Potongan Keterlambatan:
        // Dinonaktifkan sementara (di-0-kan) sesuai arahan manajemen sambil menunggu dirumuskan kembali
        if (in_array($code, ['potongan_terlambat', 'potongan_terlambat_harian'])) {
            $totalLateMins = (int) ($variables['total_late_minutes'] ?? 0);
            $lateOccurrences = (int) ($variables['total_late_occurrences'] ?? 0);

            return [
                'amount' => 0,
                'formula' => "Potongan keterlambatan dinonaktifkan sementara",
                'matched_variables' => [
                    'total_late_deduction' => 0,
                    'total_late_minutes' => $totalLateMins,
                    'total_late_occurrences' => $lateOccurrences,
                ]
            ];
        }

        // Aturan Khusus Bonus Kerajinan:
        // Diambil dalam 1 periode bulan (contoh: 30 atau 31 hari).
        // Jatah libur diabaikan (tidak boleh ada jatah libur yang dihitung).
        // Masuk full (sesuai total hari sebulan) -> diterima full.
        // Ketika ada yang tidak masuk sesuaikan dengan rumus (0: 500k/200k, 1: 300k/150k, 2: 200k/100k, 3: 100k/50k, >3: 0).
        if ($code === 'kerajinan') {
            $attendDays = (float) ($variables['total_attendance_days'] ?? 0);
            $unpaid = (float) ($variables['total_unpaid_leave_days'] ?? 0);

            $kerajinanMonthDays = (float) ($variables['total_days_in_month'] ?? $variables['total_period_days'] ?? 30);
            if ($kerajinanMonthDays <= 0) {
                $kerajinanMonthDays = 30;
            }
            $kerajinanMissingDays = max(0, $kerajinanMonthDays - $attendDays);
            $kerajinanAbsenEffectif = max($kerajinanMissingDays, $unpaid);

            $isProbation = (int) ($variables['is_probation'] ?? 0);
            if ($isProbation) {
                if ($kerajinanAbsenEffectif == 0) {
                    $kerajinanAmount = 200000;
                } elseif ($kerajinanAbsenEffectif == 1) {
                    $kerajinanAmount = 150000;
                } elseif ($kerajinanAbsenEffectif == 2) {
                    $kerajinanAmount = 100000;
                } elseif ($kerajinanAbsenEffectif == 3) {
                    $kerajinanAmount = 50000;
                } else {
                    $kerajinanAmount = 0;
                }
            } else {
                if ($kerajinanAbsenEffectif == 0) {
                    $kerajinanAmount = 500000;
                } elseif ($kerajinanAbsenEffectif == 1) {
                    $kerajinanAmount = 300000;
                } elseif ($kerajinanAbsenEffectif == 2) {
                    $kerajinanAmount = 200000;
                } elseif ($kerajinanAbsenEffectif == 3) {
                    $kerajinanAmount = 100000;
                } else {
                    $kerajinanAmount = 0;
                }
            }

            return [
                'amount' => $this->money($kerajinanAmount),
                'formula' => "Kerajinan: Target {$kerajinanMonthDays} hari, Hadir {$attendDays} hari (Absen: {$kerajinanAbsenEffectif} hari)",
                'matched_variables' => [
                    'total_days_in_month' => $kerajinanMonthDays,
                    'total_attendance_days' => $attendDays,
                    'total_unpaid_leave_days' => $kerajinanAbsenEffectif,
                    'kerajinan_absen_efektif' => $kerajinanAbsenEffectif,
                    'is_probation' => $isProbation,
                ]
            ];
        }

        // Aturan Khusus Gaji Harian (DAILY_PAID_WEEKLY, DAILY_PAID_DAILY, DAILY_PAID_MONTHLY):
        // Pendapatan (gaji_mingguan, gaji_harian_per_bulan, gaji_harian) sudah dihitung per hari kehadiran (gaji_harian x total_attendance_days).
        // Hari tidak masuk (alpha/unpaid leave) otomatis tidak dibayar (no work no pay).
        // Maka TIDAK BOLEH dipotong absen lagi agar tidak terjadi potongan ganda.
        $salaryType = strtoupper($variables['salary_cycle_type'] ?? '');
        $isDailySchema = str_contains($salaryType, 'DAILY_') || in_array($salaryType, ['DAILY', 'WEEKLY', 'WEEKLY_PAYMENT']);
        if (in_array($code, ['potongan_absen_harian', 'potongan_absen']) && ($isDailySchema || !empty($variables['gaji_harian']))) {
            return [
                'amount' => 0,
                'formula' => "Gaji harian berbasis kehadiran ({$attendDays} hari hadir), tidak dipotong absen",
                'matched_variables' => [
                    'total_attendance_days' => $attendDays,
                    'total_unpaid_leave_days' => $unpaid,
                ]
            ];
        }

        if ($comp->has_rules && !empty($comp->formula)) {
            // Jika gaji pokok sudah diprorata progresif karena libur/absen > 6 hari, jangan potong absen lagi
            if ($code === 'potongan_absen' && $totalLiburEffectif > 6) {
                return [
                    'amount' => 0,
                    'formula' => "Gaji pokok sudah diprorata berdasarkan kehadiran ({$attendDays}/{$monthDays} hari)",
                    'matched_variables' => []
                ];
            }

            $evalVars = $variables;
            if ($comp->code === 'kerajinan') {
                $salaryType = strtoupper($variables['salary_cycle_type'] ?? '');
                $attendDays = (float) ($variables['total_attendance_days'] ?? 0);
                $unpaid = (float) ($variables['total_unpaid_leave_days'] ?? 0);
                if (str_contains($salaryType, 'DAILY_')) {
                    $kerajinanWorkDays = (float) ($variables['total_working_days'] ?? 26);
                    $kerajinanMissingDays = max(0, $kerajinanWorkDays - $attendDays);
                    $kerajinanAbsenEffectif = max($kerajinanMissingDays, $unpaid);
                } else {
                    $kerajinanAbsenEffectif = $unpaid;
                }
                $evalVars['total_unpaid_leave_days'] = $kerajinanAbsenEffectif;
                $evalVars['kerajinan_absen_efektif'] = $kerajinanAbsenEffectif;
                $evalVars['total_absen_efektif'] = $kerajinanAbsenEffectif;
                $evalVars['is_probation'] = (int) ($variables['is_probation'] ?? 0);
                $evalVars['status_percobaan'] = (int) ($variables['status_percobaan'] ?? $evalVars['is_probation']);
            }

            // Evaluasi formula dari master komponen
            $baseAmount = $this->evalFormula($comp->formula, $evalVars, $matchedVars);
            $finalAmount = $this->money($baseAmount);
            return [
                'amount' => $finalAmount,
                'formula' => "Rule: {$comp->formula}",
                'matched_variables' => $matchedVars
            ];
        }

        // calculation_type and formula are now in $row (employee_salary_components)
        $calcType = $row->calculation_type ?? 'fixed';

        $baseAmount = 0;
        switch ($calcType) {
            case 'percentage':
                $rate = (float) $row->percentage;
                $baseAmount = $this->money($basic * ($rate / 100));
                $matchedVars['basic_salary'] = $basic;
                $matchedVars['rate'] = $rate;
                break;
            case 'formula':
                $baseAmount = $this->evalFormula($row->formula, $variables, $matchedVars);
                break;
            case 'fixed':
            default:
                // prioritas: amount per employee
                $baseAmount = $this->money((float) $row->amount);
                
                // Rule khusus tunjangan: jika libur (unpaid leave) > 6 hari, dihitung progresif (prorata)
                $code = strtolower($comp->code);
                
                // Jika gaji_bulanan bernilai 0 di settingan komponen karyawan, ambil dari gaji_bulanan_dasar
                if ($code === 'gaji_bulanan' && $baseAmount == 0) {
                    $baseAmount = (float) ($variables['gaji_bulanan_dasar'] ?? 0);
                }
                
                $attendDays = (float) ($variables['total_attendance_days'] ?? 0);
                
                if (($code === 'gaji_mingguan' || $code === 'gaji_harian_per_bulan') && $baseAmount == 0) {
                    $gajiHarian = (float) ($variables['gaji_harian'] ?? 0);
                    $baseAmount = $this->money($gajiHarian * $attendDays);
                }
                
                // Basis perhitungan tunjangan bulanan diambil dalam 1 periode bulan (30/31 hari penuh, jatah libur kalender diabaikan)
                $monthDays = (float) ($variables['total_days_in_month'] ?? $variables['total_period_days'] ?? 30);
                if ($monthDays <= 0) {
                    $monthDays = 30;
                }
                
                // Hari libur / tidak masuk dihitung dari (total hari sebulan - total kehadiran)
                $missingDays = max(0, $monthDays - $attendDays);
                $unpaid = (float) ($variables['total_unpaid_leave_days'] ?? 0);
                $totalLiburEffectif = max($missingDays, $unpaid);
                
                $isSalaryOrAllowance = in_array($code, ['gaji_pokok', 'tunjangan_bulanan', 'gaji_bulanan', 'tunjangan_transport', 'tunjangan_jabatan', 'tunjangan_makan']) 
                    || $comp->type === 'allowance' 
                    || str_contains($code, 'tunjangan') 
                    || str_contains(strtolower($comp->name), 'tunjangan');
                
                if ($isSalaryOrAllowance && $code !== 'kerajinan') {
                    // Jika penugasan ini merupakan prorata mutasi / penugasan sebagian periode, sesuaikan nominal bulanan
                    if (!empty($variables['is_prorated_assignment']) && !empty($variables['proration_ratio'])) {
                        $baseAmount = $this->money($baseAmount * (float) $variables['proration_ratio']);
                    }

                    // Jika dalam 1 bulan itu tidak pernah libur (atau libur <= 6 hari), gaji pokok & tunjangan diterima full.
                    // Jika libur lebih dari 6 hari (> 6 hari), dihitung progresif: (baseAmount / total_days_in_month) * total_attendance_days
                    if ($totalLiburEffectif > 6) {
                        if ($monthDays > 0) {
                            $baseAmount = $this->money(($baseAmount / $monthDays) * $attendDays);
                        }
                    }
                } elseif ($code === 'kerajinan') {
                    // Bonus Kerajinan: diambil dalam 1 periode bulan (30/31 hari), jatah libur diabaikan
                    $kerajinanMonthDays = (float) ($variables['total_days_in_month'] ?? $variables['total_period_days'] ?? 30);
                    if ($kerajinanMonthDays <= 0) {
                        $kerajinanMonthDays = 30;
                    }
                    $kerajinanMissingDays = max(0, $kerajinanMonthDays - $attendDays);
                    $kerajinanAbsenEffectif = max($kerajinanMissingDays, $unpaid);
                    
                    // Rule Bonus Kerajinan:
                    // Probation: 0 absen = 200rb, 1 absen = 150rb, 2 absen = 100rb, 3 absen = 50rb, >=4 absen = 0
                    // Reguler: 0 absen = 500rb, 1 absen = 300rb, 2 absen = 200rb, 3 absen = 100rb, >3 absen = 0
                    $isProbation = (int) ($variables['is_probation'] ?? 0);
                    if ($isProbation) {
                        if ($kerajinanAbsenEffectif == 0) {
                            $baseAmount = 200000;
                        } elseif ($kerajinanAbsenEffectif == 1) {
                            $baseAmount = 150000;
                        } elseif ($kerajinanAbsenEffectif == 2) {
                            $baseAmount = 100000;
                        } elseif ($kerajinanAbsenEffectif == 3) {
                            $baseAmount = 50000;
                        } else {
                            $baseAmount = 0;
                        }
                    } else {
                        if ($kerajinanAbsenEffectif == 0) {
                            $baseAmount = 500000;
                        } elseif ($kerajinanAbsenEffectif == 1) {
                            $baseAmount = 300000;
                        } elseif ($kerajinanAbsenEffectif == 2) {
                            $baseAmount = 200000;
                        } elseif ($kerajinanAbsenEffectif == 3) {
                            $baseAmount = 100000;
                        } else {
                            $baseAmount = 0;
                        }
                    }
                }
                break;
        }

        $formulaText = null;
        if ($comp->calculation_source === 'ATTENDANCE') {
            if ($comp->calculation_method === 'PER_DAY') {
                $multiplier = ($comp->type === 'deduction') 
                    ? (float) ($variables['total_unpaid_leave_days'] ?? 0)
                    : (float) ($variables['total_attendance_days'] ?? 0);
                
                if ($comp->type === 'deduction') {
                    $matchedVars['total_unpaid_leave_days'] = $multiplier;
                } else {
                    $matchedVars['total_attendance_days'] = $multiplier;
                }
                
                $finalAmount = $this->money($baseAmount * $multiplier);
                $formulaText = "{$baseAmount} x {$multiplier} hari";
            } elseif ($comp->calculation_method === 'PER_HOUR') {
                $multiplier = ($comp->type === 'deduction')
                    ? (float) ($variables['total_late_minutes'] ?? 0) / 60
                    : (float) ($variables['total_overtime_hours'] ?? 0);
                
                if ($comp->type === 'deduction') {
                    $matchedVars['total_late_minutes'] = (float) ($variables['total_late_minutes'] ?? 0);
                } else {
                    $matchedVars['total_overtime_hours'] = $multiplier;
                }
                
                $finalAmount = $this->money($baseAmount * $multiplier);
                $formulaText = "{$baseAmount} x {$multiplier} jam";
            } elseif ($comp->calculation_method === 'PER_OCCURRENCE') {
                $multiplier = ($comp->type === 'deduction')
                    ? (float) ($variables['total_late_occurrences'] ?? 0)
                    : 0;
                
                $matchedVars['total_late_occurrences'] = $multiplier;
                
                $finalAmount = $this->money($baseAmount * $multiplier);
                $formulaText = "{$baseAmount} x {$multiplier} kejadian";
            } else {
                $finalAmount = $baseAmount;
                if ($isSalaryOrAllowance && $code !== 'kerajinan') {
                    if ($totalLiburEffectif > 6) {
                        $formulaText = "Progresif: ({$attendDays}/{$monthDays} hari, Libur {$totalLiburEffectif} hari) = {$baseAmount}";
                    } else {
                        $formulaText = "Full: {$baseAmount} (Libur {$totalLiburEffectif} hari <= 6 hari)";
                    }
                } else {
                    $formulaText = "Fixed: {$baseAmount}";
                }
            }
        } else {
            $finalAmount = $baseAmount;
            if ($calcType === 'formula') {
                $formulaText = "Rule: {$row->formula}";
            } elseif ($calcType === 'percentage') {
                $formulaText = "{$rate}% x {$basic}";
            } elseif ($isSalaryOrAllowance && $code !== 'kerajinan') {
                if ($totalLiburEffectif > 6) {
                    $formulaText = "Progresif: ({$attendDays}/{$monthDays} hari, Libur {$totalLiburEffectif} hari) = {$baseAmount}";
                } else {
                    $formulaText = "Full: {$baseAmount} (Libur {$totalLiburEffectif} hari <= 6 hari)";
                }
            } else {
                $formulaText = "Fixed: {$baseAmount}";
            }
        }

        if ($comp->code === 'kerajinan') {
            Log::info("kerajinan baseAmount: " . $baseAmount . ", finalAmount: " . $finalAmount . ", calcType: " . $calcType);
        }
        
        return [
            'amount' => $finalAmount,
            'formula' => $formulaText
        ];
    }

    /**
     * Evaluasi formula sederhana yang nilainya dibaca dari config.
     * Variabel tersedia: {basic}/{basic_salary}; operator + - * / ( )
     * Contoh: "basic * 0.5 + 100000" atau "0.2 * basic".
     */
    private function evalFormula(?string $formula, array $variables = [], array &$matchedVars = []): float
    {
        if (!$formula) {
            return 0;
        }

        $expr = trim($formula);
        foreach ($variables as $key => $val) {
            $val = (float) $val;
            
            // Check if variable is used
            $isMatched = false;
            
            // Ganti {variable_name}
            $newExpr = str_ireplace('{' . $key . '}', (string) $val, $expr);
            if ($newExpr !== $expr) {
                $expr = $newExpr;
                $isMatched = true;
            }
            
            // Ganti variable_name langsung tanpa kurung kurawal
            $newExpr = preg_replace('/\b' . preg_quote($key, '/') . '\b/i', (string) $val, $expr);
            if ($newExpr !== $expr) {
                $expr = $newExpr;
                $isMatched = true;
            }
            
            if ($isMatched) {
                $matchedVars[$key] = $val;
            }
        }

        // Alias tambahan
        $basic = (float) ($variables['basic_salary'] ?? 0);
        $newExpr = str_ireplace(['{basic}', 'basic', 'gajipokok'], (string) $basic, $expr);
        if ($newExpr !== $expr) {
            $expr = $newExpr;
            $matchedVars['basic_salary'] = $basic;
        }
        $expr = trim($expr);

        // 1. Resolve nested min() dan max() dari dalam ke luar
        $prevExpr = null;
        $safetyLimit = 10;
        while ($prevExpr !== $expr && $safetyLimit-- > 0) {
            $prevExpr = $expr;
            $expr = preg_replace_callback('/\b(min|max)\s*\(([^()]+)\)/i', function($m) {
                $fn = strtolower($m[1]);
                $args = array_map(function($arg) {
                    $arg = trim($arg);
                    if (preg_match('#^[0-9+\-*/().\s]+$#', $arg)) {
                        return (float) @eval('return ' . $arg . ';');
                    }
                    return (float) $arg;
                }, explode(',', $m[2]));
                return (string) ($fn === 'min' ? min($args) : max($args));
            }, $expr);
        }

        // 2. Resolve nested Excel-style IF(condition, true_val, false_val)
        while (preg_match('/\bIF\s*\(/i', $expr)) {
            if (!preg_match_all('/\bIF\s*\(/i', $expr, $matches, PREG_OFFSET_CAPTURE)) {
                break;
            }
            $lastMatch = end($matches[0]);
            $startPos = $lastMatch[1];
            $parenStart = strpos($expr, '(', $startPos);
            
            $depth = 1;
            $len = strlen($expr);
            $parenEnd = false;
            $commaPositions = [];
            
            for ($i = $parenStart + 1; $i < $len; $i++) {
                $char = $expr[$i];
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $parenEnd = $i;
                        break;
                    }
                } elseif ($char === ',' && $depth === 1) {
                    $commaPositions[] = $i;
                }
            }
            
            if ($parenEnd === false || count($commaPositions) < 2) {
                break;
            }
            
            $cond = substr($expr, $parenStart + 1, $commaPositions[0] - $parenStart - 1);
            $then = substr($expr, $commaPositions[0] + 1, $commaPositions[1] - $commaPositions[0] - 1);
            $else = substr($expr, $commaPositions[1] + 1, $parenEnd - $commaPositions[1] - 1);
            
            $replacement = '((' . trim($cond) . ') ? (' . trim($then) . ') : (' . trim($else) . '))';
            $expr = substr_replace($expr, $replacement, $startPos, $parenEnd - $startPos + 1);
        }

        $expr = trim($expr);

        // 3. Resolve standard if / else if / else syntax
        $converted = preg_replace('/\belse\s+if\b/i', 'elseif', $expr);
        if (preg_match('/\bif\s*\(/i', $converted)) {
            $converted = preg_replace_callback('/(?:if|elseif)\s*\(([^()]+)\)\s*\{?\s*([^{};]+?)\s*\}?\s*(?=elseif|else|$)/i', function($m) {
                return 'if (' . trim($m[1]) . ') return ' . trim($m[2]) . '; ';
            }, $converted);

            $converted = preg_replace_callback('/else\s*\{?\s*([^{};]+?)\s*\}?\s*$/i', function($m) {
                return 'else return ' . trim($m[1]) . ';';
            }, $converted);

            // Validasi keamanan: hanya izinkan karakter dan token aman
            if (!preg_match('#^[0-9+\\-*/().\\s<>=!&|?:;a-zA-Z]+$#', $converted)) {
                Log::warning('Formula if-else mengandung karakter tidak valid', ['formula' => $converted]);
                return 0;
            }

            $disallowed = ['system', 'exec', 'shell', 'passthru', 'include', 'require', 'file', 'read', 'write', 'unlink', 'delete', 'update', 'insert', 'drop', 'table'];
            foreach ($disallowed as $bad) {
                if (preg_match('/\b' . $bad . '\b/i', $converted)) {
                    Log::warning('Formula mengandung kata terlarang', ['word' => $bad]);
                    return 0;
                }
            }

            try {
                $result = eval($converted);
                return $this->money((float) $result);
            } catch (\Throwable $e) {
                Log::warning('Gagal evaluasi formula if-else payroll', ['formula' => $converted, 'error' => $e->getMessage()]);
                return 0;
            }
        }

        // 4. Evaluasi ekspresi aritmatika / ternary biasa
        if (!preg_match('#^[0-9+\\-*/().\\s<>=!&|?:]+$#', $expr)) {
            Log::warning('Formula payroll tidak valid', ['formula' => $expr]);

            return 0;
        }

        try {
            $result = @eval('return ' . $expr . ';');

            return $this->money((float) $result);
        } catch (\Throwable $e) {
            Log::warning('Gagal evaluasi formula payroll', ['formula' => $expr, 'error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Gaji pokok (dasar) employee: dari employee_payroll_settings.basic_salary,
     * fallback ke komponen 'Gaji Pokok' bila ada.
     */
    private function basicSalary(Employee $employee, \App\Models\EmployeeAssignment $assignment, Carbon $date): float
    {
        $global = PayrollComponent::where('code', 'gaji_pokok')->where('is_active', true)->first();
        if ($global) {
            $row = EmployeeSalaryComponent::where('employee_id', $employee->id)
                ->where(function($q) use ($assignment) {
                    $q->whereNull('employee_assignment_id')
                      ->orWhere('employee_assignment_id', $assignment->id);
                })
                ->where('payroll_component_id', $global->id)
                ->where('is_active', true)
                ->where('effective_from', '<=', $date->toDateString())
                ->first();
            if ($row) {
                return (float) $row->amount;
            }

            if ($assignment && $assignment->position_id) {
                $posRow = \App\Models\PositionPayrollComponent::where('position_id', $assignment->position_id)
                    ->where('payroll_component_id', $global->id)
                    ->first();
                if ($posRow) {
                    return (float) $posRow->fixed_amount;
                }
            }
        }

        return 0;
    }

    /**
     * Hitung iuran BPJS bagian karyawan untuk periode.
     * Dasar BPJS = bpjs_basis_total (gaji pokok + tunjangan tetap). Tarif dari bpjs_settings.
     */
    private function computeBpjs(Employee $employee, array $variables, Carbon $date): array
    {
        $settings = BpjsSetting::where('is_active', true)
            ->where('effective_date', '<=', $date->toDateString())
            ->get()
            ->groupBy('bpjs_type')
            ->map(fn ($rows) => $rows->sortByDesc('effective_date')->first());

        $items = [];
        $totalEmployee = 0;
        $totalCompany = 0;

        $dependentCount = \Illuminate\Support\Facades\DB::table('employee_families')
            ->where('employee_id', $employee->id)
            ->where('is_dependent', true)
            ->count();

        $basisBpjs = $variables['bpjs_basis_total'] ?? ($variables['basic_salary'] ?? 0);

        foreach ($settings as $type => $setting) {
            $base = $basisBpjs;
            if ($setting->max_limit && $base > $setting->max_limit) {
                $base = (float) $setting->max_limit; // cap configurable
            }
            
            $employeeRate = (float) $setting->employee_rate;
            
            // Logika BPJS Kesehatan Tambahan untuk Anggota Keluarga > 4 (Total maks 5 termasuk pegawai)
            if (str_contains(strtolower($type), 'kesehatan') || str_contains(strtolower($setting->name ?? ''), 'kesehatan')) {
                if ($dependentCount > 4) {
                    $extraDependents = $dependentCount - 4;
                    // Setiap anggota keluarga tambahan menambah potongan 1% bagi pegawai
                    $employeeRate += ($extraDependents * 1.0); 
                }
            }

            $employeeAmt = $this->money($base * ($employeeRate / 100));
            $companyAmt = $this->money($base * ((float) $setting->company_rate / 100));

            $items[] = [
                'type' => $type,
                'name' => $setting->name ?? ucfirst($type),
                'employee_rate' => $employeeRate,
                'company_rate' => (float) $setting->company_rate,
                'base' => $base,
                'employee_amount' => $employeeAmt,
                'company_amount' => $companyAmt,
                'is_tax_deductible' => (bool) $setting->is_tax_deductible,
            ];
            $totalEmployee += $employeeAmt;
            $totalCompany += $companyAmt;
        }

        return [
            'items' => $items,
            'employee_total' => $this->money($totalEmployee),
            'company_total' => $this->money($totalCompany),
        ];
    }

    private function computeTax(Employee $employee, array $components, Carbon $date): array
    {
        $taxes = [];
        $totalMonthlyTax = 0;

        // 1. Ambil semua rule pajak flat aktif
        $rules = \App\Models\TaxRule::where('is_active', true)
            ->where('effective_date', '<=', $date->toDateString())
            ->with('components')
            ->get();

        foreach ($rules as $rule) {
            $taxableBase = 0;
            $componentIds = $rule->components->pluck('id')->toArray();
            
            foreach ($components as $comp) {
                if ($comp['type'] === 'earning' && in_array($comp['payroll_component_id'], $componentIds)) {
                    $taxableBase += $comp['amount'];
                }
            }

            if ($taxableBase > 0 && $rule->rate > 0) {
                $taxAmount = $this->money($taxableBase * ((float)$rule->rate / 100));
                
                $taxes[] = [
                    'rule' => $rule->name,
                    'rule_id' => $rule->id,
                    'rate' => $rule->rate,
                    'taxable_base' => $taxableBase,
                    'monthly_tax' => $taxAmount,
                ];

                $totalMonthlyTax += $taxAmount;
            }
        }

        // 2. Perhitungan PPh 21 Progresif (jika punya PTKP)
        $hasPph21Component = collect($components)->contains('code', 'pph21');

        if ($employee->ptkp_status && !$hasPph21Component) {
            $ptkp = \App\Models\PtkpSetting::where('status', $employee->ptkp_status)
                ->where('is_active', true)
                ->first();
                
            if ($ptkp) {
                $taxableBruto = 0;
                foreach ($components as $comp) {
                    if ($comp['type'] === 'earning') {
                        $taxableBruto += $comp['amount'];
                    }
                }

                $biayaJabatan = min($taxableBruto * 0.05, 500000);
                $netoSebulan = max($taxableBruto - $biayaJabatan, 0);
                $netoSetahun = $netoSebulan * 12;
                $pkpSetahun = max($netoSetahun - $ptkp->ptkp_amount, 0);
                
                $pkpSetahun = floor($pkpSetahun / 1000) * 1000; // Pembulatan ke bawah ribuan

                $brackets = \App\Models\TaxBracket::orderBy('min_amount')->get();
                $taxAmountSetahun = 0;
                $remainingPkp = $pkpSetahun;

                foreach ($brackets as $bracket) {
                    if ($remainingPkp <= 0) break;
                    $bracketRange = $bracket->max_amount ? ($bracket->max_amount - $bracket->min_amount) : $remainingPkp;
                    $taxableInBracket = min($remainingPkp, $bracketRange);
                    $taxAmountSetahun += $taxableInBracket * ($bracket->rate / 100);
                    $remainingPkp -= $taxableInBracket;
                }

                $taxAmountSebulan = $taxAmountSetahun / 12;

                if ($taxAmountSebulan > 0) {
                    $taxAmount = $this->money($taxAmountSebulan);
                    $taxes[] = [
                        'rule' => 'PPh 21 (' . $employee->ptkp_status . ')',
                        'rule_id' => null,
                        'rate' => null,
                        'taxable_base' => $taxableBruto,
                        'monthly_tax' => $taxAmount,
                    ];
                    $totalMonthlyTax += $taxAmount;
                }
            }
        }

        return [
            'total_monthly_tax' => $totalMonthlyTax,
            'details' => $taxes
        ];
    }

    public function calculateForAssignment(Employee $employee, \App\Models\EmployeeAssignment $assignment, PayrollPeriod $period, $assumeFullAttendance = false, bool $isBonusOnly = false): array
    {
        $date = Carbon::parse($period->end_date ?: $period->start_date);
        
        $basic = 0;
        if (!$isBonusOnly) {
            $basic = $this->basicSalary($employee, $assignment, $date);
        }

        $daysInPeriod = 30;
        if ($period->start_date && $period->end_date) {
            $daysInPeriod = Carbon::parse($period->start_date)->diffInDays(Carbon::parse($period->end_date)) + 1;
        } else {
            $daysInPeriod = Carbon::parse($date)->daysInMonth;
        }

        // Tentukan batas tanggal aktif penugasan (assignment) di dalam periode payroll ini
        $pPeriodStart = Carbon::parse($period->start_date ?: $date->copy()->startOfMonth());
        $pPeriodEnd   = Carbon::parse($period->end_date ?: $date->copy()->endOfMonth());

        $assignStart = $pPeriodStart->copy();
        if ($assignment->join_date) {
            $jDate = Carbon::parse($assignment->join_date);
            if ($jDate->gt($assignStart)) {
                $assignStart = $jDate;
            }
        }

        $assignEnd = $pPeriodEnd->copy();
        if ($assignment->end_date) {
            $eDate = Carbon::parse($assignment->end_date);
            if ($eDate->lt($assignEnd)) {
                $assignEnd = $eDate;
            }
        }

        // Hitung jumlah hari aktif penugasan di dalam periode
        if ($assignStart->gt($pPeriodEnd) || $assignEnd->lt($pPeriodStart)) {
            $activePeriodDays = 0;
        } else {
            $activePeriodDays = $assignStart->diffInDays($assignEnd) + 1;
        }

        $isProratedAssignment = ($activePeriodDays < $daysInPeriod && $activePeriodDays > 0);
        $prorationRatio = $daysInPeriod > 0 ? min(1.0, max(0.0, $activePeriodDays / $daysInPeriod)) : 1.0;

        // Prorasi gaji pokok jika penugasan aktif sebagian periode (misal mutasi di tengah bulan)
        if ($isProratedAssignment && !$isBonusOnly) {
            $basic = $this->money($basic * $prorationRatio);
        }

        // Aturan Khusus Executive: Department atau Position CEO dan CFO dianggap masuk full 1 bulan tanpa libur
        $deptName = strtoupper(trim($assignment->department?->name ?? ''));
        $deptCode = strtoupper(trim($assignment->department?->code ?? ''));
        $posName  = strtoupper(trim($assignment->position?->name ?? ''));
        $posCode  = strtoupper(trim($assignment->position?->code ?? ''));
        $isExecutive = $employee->isExecutive()
            || in_array($deptName, ['CEO', 'CFO'])
            || in_array($deptCode, ['CEO', 'CFO'])
            || in_array($posName, ['CEO', 'CFO'])
            || in_array($posCode, ['CEO', 'CFO'])
            || str_contains($posName, 'CHIEF EXECUTIVE')
            || str_contains($posName, 'CHIEF FINANCIAL')
            || in_array($employee->id, [268, 335]);

        if ($isExecutive) {
            $assumeFullAttendance = true;
        }

        if ($assumeFullAttendance === true || $isBonusOnly) {
            $totalOvertimeMinutes = 0;
            $totalOvertimeHours = 0;
            $totalLateMinutes = 0;
            $totalLateOccurrences = 0;
            $totalLateDeduction = 0;
            $totalAbsentDays = 0;
            $totalDayOff = 0;
            $totalAttendanceDays = $activePeriodDays;
            $totalWorkingDays = $activePeriodDays;
            $hadirCount = $activePeriodDays;
            $alphaCount = 0;
            $liburCount = 0;
            $cutiCount = 0;
        } elseif (is_array($assumeFullAttendance)) {
            $totalWorkingDays = $assumeFullAttendance['working_days'] ?? 22;
            $totalAttendanceDays = $assumeFullAttendance['present_days'] ?? 22;
            $totalAbsentDays = $assumeFullAttendance['absent_days'] ?? 0;
            $totalLateMinutes = $assumeFullAttendance['late_minutes'] ?? 0;
            $totalLateOccurrences = $assumeFullAttendance['late_occurrences'] ?? 0;
            $totalOvertimeHours = $assumeFullAttendance['overtime_hours'] ?? 0;
            $totalOvertimeMinutes = $totalOvertimeHours * 60;
            $totalDayOff = $assumeFullAttendance['day_off'] ?? 4;
            $hadirCount = $totalAttendanceDays;
            $alphaCount = $totalAbsentDays;
            $liburCount = $totalDayOff;
            $cutiCount = $assumeFullAttendance['paid_leave_days'] ?? 0;

            // Potongan keterlambatan dinonaktifkan sementara (di-0-kan)
            $totalLateDeduction = 0;
        } else {
            // Kumpulkan data kehadiran hanya pada rentang aktif penugasan ini
            $pStart = $assignStart->toDateString();
            $pEnd   = $assignEnd->toDateString();
            $periodStartDate = $pStart;
            $periodEndDate   = $pEnd;
            $attendances = \App\Models\Attendance::with('shift')
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$pStart, $pEnd])
                ->where(function ($q) use ($assignment) {
                    $q->whereNull('employee_assignment_id')
                      ->orWhere('employee_assignment_id', $assignment->id);
                })
                ->get();
            
            $totalOvertimeMinutes = 0;
            foreach ($attendances as $att) {
                $totalOvertimeMinutes += $this->getValidOvertimeMinutes($att);
            }
            $totalOvertimeHours = round($totalOvertimeMinutes / 60, 2);
            $empShifts = \App\Models\EmployeeShift::with('shift')
                ->where('employee_id', $employee->id)
                ->where('effective_date', '<=', $periodEndDate)
                ->where(function($sq) use ($periodStartDate) {
                    $sq->whereNull('end_date')->orWhere('end_date', '>=', $periodStartDate);
                })
                ->where('is_active', true)
                ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderByDesc('effective_date')
                ->get();

            $totalLateMinutes = 0;
            $totalLateOccurrences = 0;
            $totalLateDeduction = 0;
            foreach ($attendances as $att) {
                $attDateStr = \Carbon\Carbon::parse($att->date)->toDateString();
                $matchingShifts = $empShifts->filter(function($s) use ($attDateStr) {
                    $effDate = $s->effective_date instanceof \Carbon\Carbon ? $s->effective_date->toDateString() : substr((string)$s->effective_date, 0, 10);
                    $endDate2 = $s->end_date ? ($s->end_date instanceof \Carbon\Carbon ? $s->end_date->toDateString() : substr((string)$s->end_date, 0, 10)) : null;
                    return $effDate <= $attDateStr && (!$endDate2 || $endDate2 >= $attDateStr);
                });
                $empShift = $matchingShifts->first(fn($s) => !is_null($s->end_date)) ?? $matchingShifts->first();
                $shiftObj = $att->shift ?? $empShift?->shift;

                $lateM = (int) ($att->late_minutes ?? 0);
                if ($att->clock_in_at && $shiftObj && $shiftObj->clock_in) {
                    $clockInCarbon = \Carbon\Carbon::parse($att->clock_in_at)->timezone('Asia/Jakarta');
                    $clockInTime  = (int) $clockInCarbon->format('H') * 60 + (int) $clockInCarbon->format('i');
                    $shiftInParts = explode(':', $shiftObj->clock_in);
                    $shiftInTime  = (int) ($shiftInParts[0] ?? 0) * 60 + (int) ($shiftInParts[1] ?? 0);
                    $tolerance    = (int) ($shiftObj->late_tolerance_minutes ?? 0);
                    if ($clockInTime > ($shiftInTime + $tolerance)) {
                        $lateM = $clockInTime - ($shiftInTime + $tolerance);
                    } else {
                        $lateM = 0;
                    }
                }

                $totalLateMinutes += $lateM;
                if ($lateM > 0) {
                    $totalLateOccurrences++;
                    // Potongan keterlambatan dinonaktifkan sementara (di-0-kan)
                    $totalLateDeduction = 0;
                }
            }
            
            $hadirCount = $attendances->whereIn('status', ['present', 'late', 'early_leave'])->count();
            $alphaCount = $attendances->where('status', 'absent')->count();
            $liburCount = $attendances->whereIn('status', ['day_off', 'holiday', 'off'])->count();
            $cutiCount  = $attendances->whereIn('status', ['leave', 'permit', 'sick', 'permission'])->count();

            $totalAttendanceDays = $hadirCount;

            // Hitung hari libur standar kalender (misal hari Minggu) dalam rentang aktif penugasan
            $calendarDaysOff = 0;
            $curr = $assignStart->copy();
            while ($curr->lte($assignEnd)) {
                if ($curr->isSunday()) {
                    $calendarDaysOff++;
                }
                $curr->addDay();
            }
            if ($calendarDaysOff == 0 && $activePeriodDays >= 7) {
                $calendarDaysOff = (int) floor($activePeriodDays / 7);
            }
            $standardWorkingDays = max(1, $activePeriodDays - $calendarDaysOff);

            $salaryCycle = $assignment->salary_cycle_type ?? $period->cycle_type ?? 'MONTHLY_PAID_MONTHLY';
            $isDailyOrWeekly = in_array($salaryCycle, [
                'DAILY_PAID_WEEKLY', 'DAILY_PAID_DAILY', 'DAILY_PAID_MONTHLY',
                'weekly_payment', 'weekly_payslip', 'weekly', 'daily'
            ]);

            // Jika belum ada record absensi terdaftar pada periode ini
            if ($attendances->isEmpty()) {
                $totalDayOff = $calendarDaysOff;
                $totalWorkingDays = $standardWorkingDays;
                
                if ($isDailyOrWeekly) {
                    // Karyawan berbasis harian/mingguan: jika tidak ada transaksi absensi sama sekali, kehadiran = 0
                    $totalAttendanceDays = 0;
                    $hadirCount = 0;
                    $totalAbsentDays = 0;
                    $alphaCount = 0;
                    $liburCount = 0;
                    $cutiCount  = 0;
                } else {
                    // Karyawan bulanan tetap: diasumsikan hadir penuh untuk gaji pokok bulanan
                    $totalAttendanceDays = $totalWorkingDays;
                    $totalAbsentDays = 0;
                    $hadirCount = $totalAttendanceDays;
                    $liburCount = $totalDayOff;
                    $alphaCount = 0;
                    $cutiCount  = 0;
                }
            } else {
                $totalDayOff = $liburCount > 0 ? $liburCount : $calendarDaysOff;
                $totalAbsentDays = $alphaCount;
                $totalWorkingDays = $standardWorkingDays;
            }
        }

        // Menyusun variabel dinamis utama
        $daysInMonth = $daysInPeriod;
        if (is_array($assumeFullAttendance) && isset($assumeFullAttendance['days_in_month'])) {
            $daysInMonth = $assumeFullAttendance['days_in_month'];
        } elseif ($period->cycle_type === 'monthly' && (!$period->start_date || !$period->end_date)) {
            $daysInMonth = Carbon::parse($date)->daysInMonth;
        }

        $ratePerDay = $daysInMonth > 0 ? round($basic / $daysInMonth) : 0;
        $ratePerHour = $daysInMonth > 0 ? round(($basic / $daysInMonth) / 8) : 0;

        $empStatus = strtolower(trim($assignment->employment_status ?? $employee->employment_status ?? ''));
        $isProbation = in_array($empStatus, ['probation', 'percobaan']) ? 1 : 0;

        $variables = [
            'salary_cycle_type' => $assignment->salary_cycle_type ?? $period->cycle_type ?? 'MONTHLY_PAID_MONTHLY',
            'basic_salary' => $basic,
            'is_probation' => $isProbation,
            'status_percobaan' => $isProbation,
            'employment_status' => $empStatus,
            'total_working_days' => $totalWorkingDays,
            'total_days_in_month' => $daysInMonth,
            'total_period_days' => $daysInPeriod,
            'active_period_days' => $activePeriodDays,
            'is_prorated_assignment' => $isProratedAssignment ? 1 : 0,
            'proration_ratio' => $prorationRatio,
            'total_working_month_days' => max(1, $daysInMonth - ($totalDayOff ?? 0)),
            'total_attendance_days' => $totalAttendanceDays,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'total_overtime_hours' => $totalOvertimeHours,
            'total_late_minutes' => $totalLateMinutes,
            'total_late_occurrences' => $totalLateOccurrences,
            'total_late_deduction' => $totalLateDeduction,
            'late_deduction' => $totalLateDeduction,
            'potongan_terlambat' => $totalLateDeduction,
            'total_unpaid_leave_days' => $totalAbsentDays,
            'total_paid_leave_days' => $cutiCount ?? 0,
            'total_day_off' => $totalDayOff ?? 0,
            'rate_per_day' => $ratePerDay,
            'rate_per_hour' => $ratePerHour,
        ];

        // Inject gaji_harian from employee/assignment salary component
        $salaryType = $assignment->salary_cycle_type ?? null;
        if ($salaryType && $salaryType !== 'MONTHLY_PAID_MONTHLY') {
            // Find the gaji_harian component amount for this employee/assignment
            $gajiHarianComp = PayrollComponent::where('code', 'gaji_harian')->where('is_active', true)->first();
            if ($gajiHarianComp) {
                $gajiHarianRow = EmployeeSalaryComponent::where('employee_id', $employee->id)
                    ->where('payroll_component_id', $gajiHarianComp->id)
                    ->where('is_active', true)
                    ->where(function($q) use ($date) {
                        $q->whereNull('effective_from')
                          ->orWhere('effective_from', '<=', $date->toDateString());
                    })
                    ->where(function($q) use ($assignment) {
                        $q->whereNull('employee_assignment_id')
                          ->orWhere('employee_assignment_id', $assignment->id);
                    })
                    ->first();
                if ($gajiHarianRow) {
                    $variables['gaji_harian'] = (float) $gajiHarianRow->amount;
                }
            }
        }
        $variables['salary_cycle_type'] = $salaryType;

        // 1. Resolve komponen gaji (pendapatan & potongan rutin)
        $components = $this->resolveEmployeeComponents($employee, $assignment, $date, $variables, $isBonusOnly, $period);

        // === RINGKAS KOMPONEN ===
        $earnings = collect($components)->where('type', 'earning');
        $deductions = collect($components)->where('type', 'deduction');

        $totalAllowance = $this->money($earnings->sum('amount'));
        $totalOtherDeduction = $this->money($deductions->sum('amount'));
        
        // Taxable gross untuk PPh21 = jumlah earning taxable (config is_taxable)
        $taxableEarnings = $this->money($earnings->where('is_taxable', true)->sum('amount'));

        // Basis BPJS umumnya Gaji Pokok + Tunjangan Tetap
        $bpjsBasisTotal = $earnings->where('is_bpjs_basis', true)->sum('amount');
        if ($bpjsBasisTotal == 0) {
            $bpjsBasisTotal = $basic;
        }

        // Update variabel dinamis dengan hasil yang baru dikalkulasi (agar bisa dipakai di rule selanjutnya jika butuh)
        $variables['total_allowance'] = $totalAllowance;
        $variables['taxable_earnings'] = $taxableEarnings;
        $variables['bpjs_basis_total'] = $this->money($bpjsBasisTotal);

        // === BPJS ===
        // BPJS is now calculated dynamically from payroll_components table
        $bpjsEmployee = 0;
        $bpjs = ['items' => [], 'employee_total' => 0, 'company_total' => 0];

        // === PAJAK ===
        // Note: For now we don't compute tax for bonus-only slips.
        // It should ideally be calculated cumulatively, but for simplicity we skip.
        $tax = ['total_monthly_tax' => 0, 'details' => []];
        if (!$isBonusOnly) {
            $tax = $this->computeTax($employee, $components, $date);
        }

        // === OVERTIME (LEMBUR) ===
        if ($assumeFullAttendance || $isBonusOnly) {
            $totalOvertimeMinutes = 0;
        }

        // Default formula: 1/173 * Basic Salary per hour. (Overtime pay per minute = (1/173 * basic) / 60)
        $overtimeRatePerHour = $basic > 0 ? (1 / 173) * $basic : 0;
        $overtimeRatePerMinute = $overtimeRatePerHour / 60;
        $totalOvertimePay = $this->money($totalOvertimeMinutes * $overtimeRatePerMinute);

        // === DAILY RULES (ATURAN HARIAN - KETERLAMBATAN & ABSEN) ===
        // We now rely on PayrollComponent (potongan_terlambat, potongan_absen) instead of hardcoded PayrollRules.
        $dailyRulesDeduction = 0;
        $lateDeduction = $totalLateDeduction;
        $absentDeduction = 0;

        // === INCENTIVES (REWARDS) ===
        $periodStartDate = $pStart ?? \Carbon\Carbon::parse($period->start_date)->toDateString();
        $periodEndDate = $pEnd ?? \Carbon\Carbon::parse($period->end_date)->toDateString();

        $rewards = \App\Models\Reward::where('employee_id', $employee->id)
            ->where(function ($query) use ($period, $periodStartDate, $periodEndDate) {
                $query->where('payroll_period_id', $period->id)
                      ->orWhere(function ($sub) use ($periodStartDate, $periodEndDate) {
                          $sub->whereNull('payroll_period_id')
                              ->whereBetween('reward_date', [$periodStartDate, $periodEndDate]);
                      });
            })
            ->get();
        
        $totalBonus = $this->money($rewards->where('type', 'bonus')->sum('amount'));
        $totalPerformance = $this->money($rewards->where('type', 'performance')->sum('amount'));
        $totalOtherReward = $this->money($rewards->whereIn('type', ['employee_of_month', 'appreciation', 'other'])->sum('amount'));
        $totalCommission = 0;

        $totalRewardAmount = $totalPerformance + $totalOtherReward;

        // === POTONGAN LAIN-LAIN (PAYROLL DEDUCTIONS) ===
        $miscDeductions = \App\Models\PayrollDeduction::where('employee_id', $employee->id)
            ->where(function ($query) use ($period, $periodStartDate, $periodEndDate) {
                $query->where('payroll_period_id', $period->id)
                      ->orWhere(function ($sub) use ($periodStartDate, $periodEndDate) {
                          $sub->whereNull('payroll_period_id')
                              ->whereBetween('deduction_date', [$periodStartDate, $periodEndDate]);
                      });
            })
            ->get();

        $totalMiscDeduction = $this->money($miscDeductions->sum('amount'));

        // === TOTALS ===
        $grossSalary = $totalAllowance + $totalOvertimePay + $totalBonus + $totalRewardAmount + $totalCommission;
        $totalDeduction = $totalOtherDeduction + $totalMiscDeduction + $bpjsEmployee + $tax['total_monthly_tax'] + $dailyRulesDeduction;
        $netSalary = $this->money($grossSalary - $totalDeduction);

        // === DETAIL (untuk payroll_details) ===
        $details = [];
        foreach ($components as $row) {
            $details[] = [
                'payroll_component_id' => $row['payroll_component_id'],
                'label' => $row['label'],
                'type' => $row['type'],
                'amount' => $row['amount'],
                'formula' => $row['formula'] ?? null,
            ];
        }

        foreach ($tax['details'] as $taxItem) {
            $label = 'Pajak ' . $taxItem['rule'];
            $formula = 'Rp ' . number_format($taxItem['taxable_base'], 0, ',', '.');
            if ($taxItem['rate'] !== null) {
                $label .= ' (' . (float)$taxItem['rate'] . '%)';
                $formula = (float)$taxItem['rate'] . '% x ' . $formula;
            } else {
                $formula = 'Progresif atas neto bulanan ' . $formula;
            }

            $details[] = [
                'payroll_component_id' => null,
                'label' => $label,
                'type' => 'tax',
                'amount' => $taxItem['monthly_tax'],
                'formula' => $formula,
            ];
        }
        
        if ($totalOvertimePay > 0) {
            $details[] = [
                'payroll_component_id' => null,
                'label' => 'Uang Lembur (' . $totalOvertimeMinutes . ' menit)',
                'type' => 'earning',
                'amount' => $totalOvertimePay,
            ];
        }

        // Tambahkan Bonus & Reward ke rincian slip gaji (earning)
        foreach ($rewards as $reward) {
            if ($reward->amount > 0) {
                $details[] = [
                    'payroll_component_id' => null,
                    'label' => $reward->title ?: ('Bonus / Insentif (' . ucfirst($reward->type) . ')'),
                    'type' => 'earning',
                    'amount' => (float)$reward->amount,
                ];
            }
        }

        // Tambahkan Potongan Lain-Lain ke rincian slip gaji (deduction)
        foreach ($miscDeductions as $deduction) {
            if ($deduction->amount > 0) {
                $details[] = [
                    'payroll_component_id' => null,
                    'label' => $deduction->title ?: ('Potongan Lain-Lain (' . ucfirst($deduction->type) . ')'),
                    'type' => 'deduction',
                    'amount' => (float)$deduction->amount,
                ];
            }
        }

        $breakdown = [
            'basic_salary' => $basic,
            'components' => $components,
            'attendance' => [
                'total_period_days' => $daysInPeriod,
                'work_days' => $totalWorkingDays,
                'hadir' => $hadirCount,
                'libur' => $liburCount,
                'cuti' => $cutiCount,
                'alpha' => $alphaCount,
                'rate_per_day' => $ratePerDay,
                'rate_per_hour' => $ratePerHour,
            ],
            'overtime' => [
                'minutes' => $totalOvertimeMinutes,
                'amount' => $totalOvertimePay
            ],
            'daily_rules' => [
                'late_minutes' => $totalLateMinutes,
                'late_deduction' => $lateDeduction,
                'absent_days' => $totalAbsentDays,
                'absent_deduction' => $absentDeduction
            ],
            'rewards' => $rewards->map(fn($r) => ['title' => $r->title, 'amount' => $r->amount, 'type' => $r->type])->toArray(),
            'deductions' => $miscDeductions->map(fn($d) => ['title' => $d->title, 'amount' => $d->amount, 'type' => $d->type])->toArray(),
            'total_misc_deduction' => $totalMiscDeduction,
            'bpjs' => $bpjs,
            'tax' => $tax,
            'variables' => $variables,
            'net_salary' => $netSalary,
        ];

        return [
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'gross_salary' => $grossSalary,
            'total_allowance' => $totalAllowance,
            'total_overtime' => $totalOvertimePay,
            'total_bonus' => $totalBonus,
            'total_commission' => $totalCommission,
            'total_reward' => $totalRewardAmount,
            'total_deduction' => $totalDeduction,
            'total_tax' => $tax['total_monthly_tax'],
            'total_bpjs' => $bpjsEmployee,
            'net_salary' => $netSalary,
            'breakdown' => $breakdown,
            'status' => 'calculated',
            '_details' => $details,
        ];
    }

    /**
     * Hitung durasi lembur (menit) yang valid.
     * Aturan:
     * 1. Ada pengajuan lembur dari karyawan pada hari tersebut yang sudah di-approve oleh semua pihak.
     * 2. Waktu lembur dihitung per jam mulai dari jam 20:00 sampai selesai.
     */
    public function getValidOvertimeMinutes(\App\Models\Attendance $attendance): int
    {
        $dateStr = \Carbon\Carbon::parse($attendance->date)->toDateString();

        // 1. Cek pengajuan lembur yang sudah di-approve oleh semua pihak
        $ot = \App\Models\OvertimeRequest::where('employee_id', $attendance->employee_id)
            ->whereDate('date', $dateStr)
            ->where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere(function ($sub) {
                      $sub->whereHas('approval', function ($appr) {
                          $appr->where('status', 'approved');
                      });
                  });
            })
            ->first();

        if (!$ot) {
            return 0;
        }

        // 2. Waktu lembur dihitung per jam mulai dari jam 20:00 sampai selesai
        $otStart = $ot->requested_start_time ? \Carbon\Carbon::parse($ot->requested_start_time)->format('H:i:s') : '20:00:00';
        $otStartTime = \Carbon\Carbon::parse($dateStr . ' ' . $otStart);

        $endTimeRaw = $attendance->overtime_clock_out_at
            ?? $ot->actual_end_time
            ?? $attendance->clock_out_at
            ?? ($ot->requested_end_time ? $dateStr . ' ' . $ot->requested_end_time : null);

        if (!$endTimeRaw) {
            return 0;
        }

        $endTime = \Carbon\Carbon::parse($endTimeRaw);

        // Jika selesai sebelum atau tepat jam mulai lembur (20:00), tidak ada lembur
        if ($endTime->lte($otStartTime)) {
            return 0;
        }

        $diffMinutes = $otStartTime->diffInMinutes($endTime);

        return max(0, (int) $diffMinutes);
    }
}







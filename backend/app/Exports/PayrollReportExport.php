<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\CompanyGroup;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Reward;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollReportExport
{
    protected ?PayrollPeriod $period;
    protected ?int $periodId;

    public function __construct(PayrollPeriod|int|null $period = null)
    {
        if (is_int($period)) {
            $this->periodId = $period;
            $this->period = PayrollPeriod::with(['branch', 'company'])->find($period);
        } elseif ($period instanceof PayrollPeriod) {
            $this->period = $period;
            $this->periodId = $period->id;
        } else {
            $this->period = null;
            $this->periodId = null;
        }
    }

    /**
     * Resolve company or branch logo path directly from database.
     */
    private function resolveLogoPath(): ?string
    {
        $period = $this->period;

        // 1. From database: Branch logo
        if ($period?->branch && !empty($period->branch->logo)) {
            $p = storage_path('app/public/' . $period->branch->logo);
            if (file_exists($p)) return $p;
            $p2 = public_path($period->branch->logo);
            if (file_exists($p2)) return $p2;
        }

        // 2. From database: Company logo
        if ($period?->company && !empty($period->company->logo)) {
            $p = storage_path('app/public/' . $period->company->logo);
            if (file_exists($p)) return $p;
            $p2 = public_path($period->company->logo);
            if (file_exists($p2)) return $p2;
        }

        // 3. From database: Company Group logo
        if (class_exists(CompanyGroup::class)) {
            $grp = CompanyGroup::whereNotNull('logo')->first();
            if ($grp && !empty($grp->logo)) {
                $p = storage_path('app/public/' . $grp->logo);
                if (file_exists($p)) return $p;
            }
        }

        // 4. Fallback physical paths
        $fallbacks = [
            public_path('images/peby-motor-logo.png'),
            storage_path('app/public/branch-logos/peby-motor-logo.png'),
            public_path('assets/images/logo-dark.png'),
        ];
        foreach ($fallbacks as $cand) {
            if (file_exists($cand)) return $cand;
        }

        return null;
    }

    /**
     * Build the spreadsheet instance with premium corporate styling.
     */
    public function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Payroll');
        $sheet->setShowGridlines(true);
        $sheet->setPrintGridlines(true);

        $period = $this->period;
        if (!$period && $this->periodId) {
            $period = PayrollPeriod::with(['branch', 'company'])->find($this->periodId);
        }

        // 1. Fetch runs for this period
        $runs = PayrollRun::where('payroll_period_id', $period?->id)
            ->with(['employee.primaryAssignment.position', 'employee.primaryAssignment.branch', 'details'])
            ->get();

        $runEmpIds = $runs->pluck('employee_id')->filter()->toArray();

        // 2. Fetch other employees of the same branch who only have rewards in this period
        $branchId = $period?->branch_id;
        $startDate = $period?->start_date;
        $endDate = $period?->end_date;

        $otherRewards = Reward::where(function ($q) use ($period, $startDate, $endDate) {
            if ($period?->id) {
                $q->where('payroll_period_id', $period->id);
            }
            if ($startDate && $endDate) {
                $q->orWhereBetween('reward_date', [$startDate, $endDate]);
            }
        })
            ->whereNotIn('employee_id', $runEmpIds)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('employee.primaryAssignment', fn($sq) => $sq->where('branch_id', $branchId));
            })
            ->with(['employee.primaryAssignment.position', 'employee.primaryAssignment.branch'])
            ->get()
            ->groupBy('employee_id');

        // 3. Process runs
        $mechanicRows = [];
        $otherRows = [];

        foreach ($runs as $r) {
            $emp = $r->employee;
            if (!$emp) continue;

            $posRaw = strtoupper($emp->primaryAssignment?->position?->name ?? '-');
            $breakdown = $r->breakdown ?? [];
            $vars = $breakdown['variables'] ?? [];
            $att = $breakdown['attendance'] ?? [];

            $masuk = $vars['total_attendance_days'] ?? ($att['hadir'] ?? null);
            $hari = (float)($vars['gaji_harian'] ?? 0);

            // Gaji harian total (earning from attendance)
            $gajiHarian = 0;
            if (!empty($r->details)) {
                foreach ($r->details as $d) {
                    if ($d->type === 'earning' && (str_contains(strtolower($d->component_name), 'gaji') || str_contains(strtolower($d->component_code), 'gaji'))) {
                        $gajiHarian += (float)$d->amount;
                    }
                }
            }
            if ($gajiHarian == 0 && $hari > 0 && $masuk > 0) {
                $gajiHarian = $hari * $masuk;
            }

            // Categorize rewards
            $bonusService = 0;
            $bonusKerajinan = 0;
            $bonusScanner = 0;

            $rewards = $breakdown['rewards'] ?? [];
            foreach ($rewards as $rew) {
                $t = strtolower($rew['title'] ?? '');
                $amt = (float)($rew['amount'] ?? 0);

                if (str_contains($t, 'kerajinan') || str_contains($t, 'rajin')) {
                    $bonusKerajinan += $amt;
                } elseif (str_contains($t, 'scanner')) {
                    $bonusScanner += $amt;
                } else {
                    if (str_contains($posRaw, 'MEKANIK')) {
                        $bonusService += $amt;
                    } else {
                        $bonusScanner += $amt;
                    }
                }
            }

            // Kasbon deduction
            $kasBon = 0;
            $deductions = $breakdown['deductions'] ?? [];
            foreach ($deductions as $ded) {
                $t = strtolower($ded['title'] ?? '');
                $amt = (float)($ded['amount'] ?? 0);
                if (str_contains($t, 'kasbon') || str_contains($t, 'pinjaman') || str_contains($t, 'loan')) {
                    $kasBon += $amt;
                }
            }
            if ($kasBon == 0 && $r->total_deduction > 0) {
                $kasBon = (float)$r->total_deduction;
            }

            $total = (float)$r->net_salary;

            $rowItem = [
                'id' => $emp->id,
                'name' => strtoupper($emp->name),
                'posisi' => str_contains($posRaw, 'MEKANIK') ? 'MEKANIK' : $posRaw,
                'is_kepala' => str_contains($posRaw, 'KEPALA MEKANIK'),
                'masuk' => $masuk,
                'hari' => $hari > 0 ? $hari : null,
                'gaji_harian' => $gajiHarian > 0 ? $gajiHarian : null,
                'bonus_service' => $bonusService > 0 ? $bonusService : null,
                'bonus_kerajinan' => $bonusKerajinan > 0 ? $bonusKerajinan : null,
                'bonus_scanner' => $bonusScanner > 0 ? $bonusScanner : null,
                'kas_bon' => $kasBon > 0 ? $kasBon : null,
                'total' => $total,
            ];

            if (str_contains($posRaw, 'MEKANIK')) {
                $mechanicRows[] = $rowItem;
            } else {
                $otherRows[] = $rowItem;
            }
        }

        // Sort mechanics: Kepala Mekanik first, then alphabetically by name
        usort($mechanicRows, function ($a, $b) {
            if ($a['is_kepala'] !== $b['is_kepala']) {
                return $a['is_kepala'] ? -1 : 1;
            }
            return strcmp($a['name'], $b['name']);
        });

        // 4. Process other staff with rewards
        foreach ($otherRewards as $empId => $rewList) {
            $emp = $rewList->first()?->employee;
            if (!$emp) continue;

            $posRaw = strtoupper($emp->primaryAssignment?->position?->name ?? '-');

            $bonusService = 0;
            $bonusKerajinan = 0;
            $bonusScanner = 0;

            foreach ($rewList as $rew) {
                $t = strtolower($rew->title ?? '');
                $amt = (float)$rew->amount;
                if (str_contains($t, 'kerajinan')) {
                    $bonusKerajinan += $amt;
                } elseif (str_contains($t, 'scanner')) {
                    $bonusScanner += $amt;
                } else {
                    $bonusScanner += $amt;
                }
            }

            $total = $bonusService + $bonusKerajinan + $bonusScanner;

            $otherRows[] = [
                'id' => $emp->id,
                'name' => strtoupper($emp->name),
                'posisi' => $posRaw,
                'is_kepala' => str_contains($posRaw, 'KEPALA'),
                'masuk' => null,
                'hari' => null,
                'gaji_harian' => null,
                'bonus_service' => $bonusService > 0 ? $bonusService : null,
                'bonus_kerajinan' => $bonusKerajinan > 0 ? $bonusKerajinan : null,
                'bonus_scanner' => $bonusScanner > 0 ? $bonusScanner : null,
                'kas_bon' => null,
                'total' => $total,
            ];
        }

        // Sort other staff: Kepala Toko first, then Pramuniaga, etc.
        usort($otherRows, function ($a, $b) {
            if ($a['is_kepala'] !== $b['is_kepala']) {
                return $a['is_kepala'] ? -1 : 1;
            }
            return strcmp($a['name'], $b['name']);
        });

        $allRows = array_merge($mechanicRows, $otherRows);

        // 5. Logo from Database
        $logoPath = $this->resolveLogoPath();
        if ($logoPath && file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo Perusahaan');
            $drawing->setDescription('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(50);
            $drawing->setCoordinates('B1');
            $drawing->setOffsetX(8);
            $drawing->setOffsetY(8);
            $drawing->setWorksheet($sheet);
        }

        // 6. Header Information (Title, Period, Branch & Address)
        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $periodStr = '';
        if ($period?->start_date && $period?->end_date) {
            $sD = Carbon::parse($period->start_date);
            $eD = Carbon::parse($period->end_date);
            $periodStr = $sD->format('d') . ' ' . ($monthNames[$sD->month] ?? $sD->format('F'))
                . ' - '
                . $eD->format('d') . ' ' . ($monthNames[$eD->month] ?? $eD->format('F')) . ' ' . $eD->format('Y');
        } else {
            $now = Carbon::now();
            $periodStr = $now->format('d') . ' ' . ($monthNames[$now->month] ?? $now->format('F')) . ' ' . $now->format('Y');
        }

        $branchName = $period?->branch?->name ?? 'Cabang';

        // Title row
        $sheet->setCellValue('E1', 'GAJI DAN BONUS MEKANIK');
        $sheet->mergeCells('E1:L1');
        $sheet->getStyle('E1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF1B365D'));

        // Period row
        $sheet->setCellValue('E2', 'Periode: ' . $periodStr);
        $sheet->mergeCells('E2:L2');
        $sheet->getStyle('E2')->getFont()->setBold(true)->setSize(10.5)->setColor(new Color('FF475569'));

        // Branch row (sesuai nama cabang saja)
        $sheet->setCellValue('E3', 'Cabang: ' . $branchName);
        $sheet->mergeCells('E3:L3');
        $sheet->getStyle('E3')->getFont()->setSize(9.5)->setColor(new Color('FF64748B'));

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(16);
        $sheet->getRowDimension(4)->setRowHeight(12); // spacer

        // 7. Subheader Row: Branch Badge & JUMLAH GAJI (Row 5)
        $sheet->setCellValue('B5', strtoupper($branchName));
        $sheet->mergeCells('B5:D5');
        $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(10.5)->setColor(new Color('FF1B365D'));
        $sheet->getStyle('B5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B5:D5')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8EEF5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);

        $sheet->setCellValue('G5', 'JUMLAH GAJI');
        $sheet->mergeCells('G5:L5');
        $sheet->getStyle('G5')->getFont()->setBold(true)->setSize(10.5)->setColor(new Color('FF1B365D'));
        $sheet->getStyle('G5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('G5:L5')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8EEF5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(24);

        // 8. Column Headers (Row 6)
        $headers = [
            'A6' => 'NO',
            'B6' => 'NO',
            'C6' => 'NAMA KARYAWAN',
            'D6' => 'POSISI',
            'E6' => 'MASUK',
            'F6' => 'HARI',
            'G6' => 'GAJI HARIAN',
            'H6' => 'BONUS SERVICE',
            'I6' => 'BONUS KERAJINAN',
            'J6' => 'BONUS SCANNER',
            'K6' => 'KAS BON',
            'L6' => 'TOTAL (THP)',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1F4E79'], // Deep corporate navy
            ],
            'font' => [
                'bold' => true,
                'size' => 9.5,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF366092'],
                ],
            ],
        ];
        $sheet->getStyle('A6:L6')->applyFromArray($headerStyle);
        $sheet->getRowDimension(6)->setRowHeight(28);

        // 9. Data Rows (Row 7 onwards) with Zebra Striping
        $rIdx = 7;
        $globalNo = 1;

        foreach ($allRows as $r) {
            $sheet->setCellValue("A{$rIdx}", $globalNo);
            $sheet->setCellValue("B{$rIdx}", $globalNo);
            $sheet->setCellValue("C{$rIdx}", $r['name']);
            $sheet->setCellValue("D{$rIdx}", $r['posisi']);
            $sheet->setCellValue("E{$rIdx}", $r['masuk']);
            $sheet->setCellValue("F{$rIdx}", $r['hari']);
            $sheet->setCellValue("G{$rIdx}", $r['gaji_harian']);
            $sheet->setCellValue("H{$rIdx}", $r['bonus_service']);
            $sheet->setCellValue("I{$rIdx}", $r['bonus_kerajinan']);
            $sheet->setCellValue("J{$rIdx}", $r['bonus_scanner']);
            $sheet->setCellValue("K{$rIdx}", $r['kas_bon']);
            $sheet->setCellValue("L{$rIdx}", $r['total']);

            $sheet->getStyle("A{$rIdx}:B{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("D{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("E{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("F{$rIdx}:L{$rIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle("F{$rIdx}:L{$rIdx}")->getNumberFormat()->setFormatCode('#,##0');

            // Subtle zebra striping
            $rowBg = ($globalNo % 2 === 0) ? 'FFF8FAFC' : 'FFFFFFFF';
            $sheet->getStyle("A{$rIdx}:L{$rIdx}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $rowBg],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
            ]);

            // Outer border accents
            $sheet->getStyle("A{$rIdx}")->getBorders()->getLeft()->getColor()->setARGB('FF1F4E79');
            $sheet->getStyle("L{$rIdx}")->getBorders()->getRight()->getColor()->setARGB('FF1F4E79');

            $sheet->getRowDimension($rIdx)->setRowHeight(21);
            $rIdx++;
            $globalNo++;
        }

        // 10. Total Summary Row
        $totRow = $rIdx;
        $dataEnd = $totRow - 1;

        $sheet->setCellValue("A{$totRow}", 'TOTAL');
        $sheet->mergeCells("A{$totRow}:F{$totRow}");
        $sheet->getStyle("A{$totRow}")->getFont()->setBold(true)->setSize(10.5)->setColor(new Color('FF1B365D'));
        $sheet->getStyle("A{$totRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue("G{$totRow}", "=SUM(G7:G{$dataEnd})");
        $sheet->setCellValue("H{$totRow}", "=SUM(H7:H{$dataEnd})");
        $sheet->setCellValue("I{$totRow}", "=SUM(I7:I{$dataEnd})");
        $sheet->setCellValue("J{$totRow}", "=SUM(J7:J{$dataEnd})");
        $sheet->setCellValue("K{$totRow}", "=SUM(K7:K{$dataEnd})");
        $sheet->setCellValue("L{$totRow}", "=SUM(L7:L{$dataEnd})");

        $sheet->getStyle("G{$totRow}:L{$totRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("G{$totRow}:L{$totRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$totRow}:L{$totRow}")->getFont()->setBold(true)->setColor(new Color('FF1B365D'));
        $sheet->getStyle("A{$totRow}:L{$totRow}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEBF3FB'], // Soft premium highlight
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF1F4E79']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1F4E79']],
                'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF1F4E79']],
                'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF1F4E79']],
                'inside' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
            ],
        ]);
        $sheet->getRowDimension($totRow)->setRowHeight(25);

        // 11. Manual Signatures Section (Format Tanda Tangan Manual)
        $sigDateRow = $totRow + 2;
        $cityName = $period?->branch?->city ?? 'Kota Depok';
        $todayNow = Carbon::now();
        $todayIndo = $todayNow->format('d') . ' ' . ($monthNames[$todayNow->month] ?? $todayNow->format('F')) . ' ' . $todayNow->format('Y');
        $sheet->setCellValue("K{$sigDateRow}", "{$cityName}, {$todayIndo}");
        $sheet->mergeCells("K{$sigDateRow}:L{$sigDateRow}");
        $sheet->getStyle("K{$sigDateRow}")->getFont()->setItalic(true)->setSize(9)->setColor(new Color('FF64748B'));
        $sheet->getStyle("K{$sigDateRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sigHeaderRow = $totRow + 3;
        $sheet->getRowDimension($totRow + 1)->setRowHeight(12);
        $sheet->getRowDimension($totRow + 2)->setRowHeight(16);

        // Header Baris Tanda Tangan:
        // Dibuat Oleh, (Col B)
        $sheet->setCellValue("B{$sigHeaderRow}", 'Dibuat Oleh,');
        $sheet->mergeCells("B{$sigHeaderRow}:D{$sigHeaderRow}");
        $sheet->getStyle("B{$sigHeaderRow}")->getFont()->setSize(10)->setColor(new Color('FF334155'));
        $sheet->getStyle("B{$sigHeaderRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Diperiksa Oleh, (Col F to I)
        $sheet->setCellValue("F{$sigHeaderRow}", 'Diperiksa Oleh,');
        $sheet->mergeCells("F{$sigHeaderRow}:I{$sigHeaderRow}");
        $sheet->getStyle("F{$sigHeaderRow}")->getFont()->setSize(10)->setColor(new Color('FF334155'));
        $sheet->getStyle("F{$sigHeaderRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Disetujui Oleh, (Col K to L)
        $sheet->setCellValue("K{$sigHeaderRow}", 'Disetujui Oleh,');
        $sheet->mergeCells("K{$sigHeaderRow}:L{$sigHeaderRow}");
        $sheet->getStyle("K{$sigHeaderRow}")->getFont()->setSize(10)->setColor(new Color('FF334155'));
        $sheet->getStyle("K{$sigHeaderRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // 4 baris kosong untuk paraf / stempel / tanda tangan fisik
        $sheet->getRowDimension($sigHeaderRow + 1)->setRowHeight(20);
        $sheet->getRowDimension($sigHeaderRow + 2)->setRowHeight(20);
        $sheet->getRowDimension($sigHeaderRow + 3)->setRowHeight(20);
        $sheet->getRowDimension($sigHeaderRow + 4)->setRowHeight(20);

        $sigNameRow = $sigHeaderRow + 5;
        $sigTitleRow = $sigNameRow + 1;

        // Fetch names from DB or use official designations
        $empIra = Employee::where('name', 'like', '%Ira Kartika Sari%')->first();
        $nameIra = $empIra?->name ?? 'Ira Kartika Sari';
        $titleIra = 'HRGA Supervisor';

        $empAgus = Employee::where('name', 'like', '%Agus Prasetyo%')->first();
        $nameAgus = $empAgus?->name ?? 'Agus Prasetyo M';
        $titleAgus = 'HRGA Manager';

        $empIswahyuni = Employee::where('name', 'like', '%Iswahyuni%')->first();
        $nameIswahyuni = $empIswahyuni?->name ?? 'Iswahyuni';
        $titleIswahyuni = 'Finance Manager';

        $empUjang = Employee::where('name', 'like', '%Ujang Peby%')->orWhere('name', 'like', '%Peby Hidayat%')->first();
        $nameUjang = $empUjang?->name ?? 'Ujang Peby Hidayat';
        $titleUjang = 'Direktur';

        // Person 1: Ira Kartika Sari (Col B)
        $sheet->setCellValue("B{$sigNameRow}", $nameIra);
        $sheet->mergeCells("B{$sigNameRow}:D{$sigNameRow}");
        $sheet->getStyle("B{$sigNameRow}")->getFont()->setBold(true)->setSize(10)->setColor(new Color('FF0F172A'));

        $sheet->setCellValue("B{$sigTitleRow}", $titleIra);
        $sheet->mergeCells("B{$sigTitleRow}:D{$sigTitleRow}");
        $sheet->getStyle("B{$sigTitleRow}")->getFont()->setSize(9)->setColor(new Color('FF64748B'));

        // Person 2: Agus Prasetyo M (Col F:G)
        $sheet->setCellValue("F{$sigNameRow}", $nameAgus);
        $sheet->mergeCells("F{$sigNameRow}:G{$sigNameRow}");
        $sheet->getStyle("F{$sigNameRow}")->getFont()->setBold(true)->setSize(10)->setColor(new Color('FF0F172A'));

        $sheet->setCellValue("F{$sigTitleRow}", $titleAgus);
        $sheet->mergeCells("F{$sigTitleRow}:G{$sigTitleRow}");
        $sheet->getStyle("F{$sigTitleRow}")->getFont()->setSize(9)->setColor(new Color('FF64748B'));

        // Person 3: Iswahyuni (Col H:I)
        $sheet->setCellValue("H{$sigNameRow}", $nameIswahyuni);
        $sheet->mergeCells("H{$sigNameRow}:I{$sigNameRow}");
        $sheet->getStyle("H{$sigNameRow}")->getFont()->setBold(true)->setSize(10)->setColor(new Color('FF0F172A'));

        $sheet->setCellValue("H{$sigTitleRow}", $titleIswahyuni);
        $sheet->mergeCells("H{$sigTitleRow}:I{$sigTitleRow}");
        $sheet->getStyle("H{$sigTitleRow}")->getFont()->setSize(9)->setColor(new Color('FF64748B'));

        // Person 4: Ujang Peby Hidayat (Col K:L)
        $sheet->setCellValue("K{$sigNameRow}", $nameUjang);
        $sheet->mergeCells("K{$sigNameRow}:L{$sigNameRow}");
        $sheet->getStyle("K{$sigNameRow}")->getFont()->setBold(true)->setSize(10)->setColor(new Color('FF0F172A'));

        $sheet->setCellValue("K{$sigTitleRow}", $titleUjang);
        $sheet->mergeCells("K{$sigTitleRow}:L{$sigTitleRow}");
        $sheet->getStyle("K{$sigTitleRow}")->getFont()->setSize(9)->setColor(new Color('FF64748B'));

        $sheet->getRowDimension($sigNameRow)->setRowHeight(18);
        $sheet->getRowDimension($sigTitleRow)->setRowHeight(16);

        // 12. Column Dimensions
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(6);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(16);
        $sheet->getColumnDimension('L')->setWidth(16);

        // 13. Page Setup for Professional Printing (Landscape A4, Fit to 1 page width)
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        return $spreadsheet;
    }

    /**
     * Download the spreadsheet as StreamedResponse.
     */
    public function download(?string $filename = null): StreamedResponse
    {
        $period = $this->period;
        if (!$filename) {
            $branchCode = $period?->branch?->name ? str_replace(' ', '_', $period->branch->name) : 'Payroll';
            $startDate = $period?->start_date ? Carbon::parse($period->start_date)->format('Ymd') : date('Ymd');
            $endDate = $period?->end_date ? Carbon::parse($period->end_date)->format('Ymd') : date('Ymd');
            $filename = "Laporan_Gaji_Mekanik_{$branchCode}_{$startDate}_{$endDate}.xlsx";
        }

        $spreadsheet = $this->buildSpreadsheet();
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

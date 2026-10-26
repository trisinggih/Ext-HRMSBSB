<?php

namespace App\Http\Controllers\Api\v1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\GeneratePayrollRequest;
use App\Http\Requests\Payroll\StorePayrollPeriodRequest;
use App\Http\Requests\Payroll\UpdatePayrollPeriodRequest;
use App\Http\Resources\Payroll\PayrollPeriodResource;
use App\Http\Resources\Payroll\PayrollRunResource;
use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Exports\PayrollReportExport;
use App\Services\PayrollCalculatorService;
use App\Services\PayrollMailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function __construct(
        private PayrollCalculatorService $calculator,
        private PayrollMailService $mailer,
    ) {
    }

    // ─── HELPER: Auto-generate nama periode berdasarkan cycle_type ─────────────
    private function generatePeriodName(string $cycleType, string $startDate, string $endDate, ?string $branchName = null): string
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $monthId = [
            1  => 'Januari', 2  => 'Februari', 3  => 'Maret',
            4  => 'April',   5  => 'Mei',       6  => 'Juni',
            7  => 'Juli',    8  => 'Agustus',   9  => 'September',
            10 => 'Oktober', 11 => 'November',  12 => 'Desember',
        ];

        $bulan = $monthId[$start->month] ?? $start->format('F');
        $tahun = $start->year;

        if ($cycleType === 'weekly_payment') {
            if ($start->month === $end->month) {
                $base = 'Pembayaran Mingguan ' . $start->format('d') . '-' . $end->format('d') . ' ' . $bulan . ' ' . $tahun;
            } else {
                $base = 'Pembayaran Mingguan ' . $start->format('d') . ' ' . $bulan . ' - ' . $end->format('d') . ' ' . ($monthId[$end->month] ?? $end->format('F')) . ' ' . $end->year;
            }
        } else {
            $base = match ($cycleType) {
                'daily'              => 'Gaji Harian ' . $start->format('d') . ' ' . $bulan . ' ' . $tahun,
                'weekly_payslip'     => 'Tunjangan Bulanan ' . $bulan . ' ' . $tahun . ' (Skema Mingguan)',
                'daily_paid_monthly' => 'Gaji Harian (Bayar Bulanan) ' . $bulan . ' ' . $tahun,
                'monthly'            => 'Gaji Bulanan ' . $bulan . ' ' . $tahun,
                default              => 'Payroll ' . $bulan . ' ' . $tahun,
            };
        }

        if ($branchName) {
            $base .= ' - Cabang ' . $branchName;
        }

        return $base;
    }

    // ============ PERIODS (CRUD admin) ============
    public function indexPeriods(Request $request): JsonResponse
    {
        $periods = PayrollPeriod::query()
            ->with(['company', 'branch'])
            ->withCount('runs')
            ->when($request->filled('period'), fn ($q) => $q->where('period', $request->period))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('cycle_type'), function($q) use ($request) {
                if ($request->cycle_type === 'monthly') {
                    if ($request->filled('category')) {
                        $q->where('cycle_type', $request->category);
                    } else {
                        $q->whereIn('cycle_type', ['monthly', 'daily_paid_monthly']);
                    }
                } else {
                    $q->where('cycle_type', $request->cycle_type);
                }
            })
            ->orderByDesc('start_date')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Daftar periode payroll',
            'data' => PayrollPeriodResource::collection($periods)->response()->getData(true),
        ]);
    }

    public function storePeriod(StorePayrollPeriodRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if ($data['cycle_type'] === 'monthly' || $data['cycle_type'] === 'daily_paid_monthly' || $data['cycle_type'] === 'weekly_payslip') {
            if (empty($data['period'])) {
                $refDate = $data['end_date'] ?? $data['start_date'] ?? now();
                $data['period'] = date('Y-m', strtotime($refDate));
            }
            // For monthly, daily_paid_monthly and weekly_payslip, start_date & end_date must strictly match the whole month
            if (empty($data['start_date']) || !str_starts_with($data['start_date'], $data['period'])) {
                $data['start_date'] = date('Y-m-01', strtotime($data['period']));
            }
            if (empty($data['end_date']) || !str_starts_with($data['end_date'], $data['period'])) {
                $data['end_date'] = date('Y-m-t', strtotime($data['period']));
            }
        } elseif ($data['cycle_type'] === 'daily') {
            // For daily, end_date is the same as start_date (1 day)
            if (empty($data['start_date'])) {
                $data['start_date'] = date('Y-m-d');
            }
            $data['end_date'] = $data['start_date'];
            $data['period']   = date('Y-m', strtotime($data['start_date']));
        } else {
            // weekly_payment / weekly: periode Jumat s/d Kamis (7 hari)
            if (!empty($data['start_date'])) {
                if (empty($data['end_date'])) {
                    $data['end_date'] = date('Y-m-d', strtotime($data['start_date'] . ' +6 days'));
                }
                $data['period'] = date('Y-m', strtotime($data['start_date']));
            } elseif (!empty($data['period'])) {
                // Cari hari Jumat pertama pada bulan tersebut
                $firstDayOfMonth = Carbon::parse($data['period'] . '-01');
                $firstFriday = $firstDayOfMonth->isFriday() ? $firstDayOfMonth : $firstDayOfMonth->next(Carbon::FRIDAY);
                $data['start_date'] = $firstFriday->toDateString();
                $data['end_date']   = $firstFriday->copy()->addDays(6)->toDateString();
            }
        }
        
        if (empty($data['name'])) {
            $branchName = !empty($data['branch_id']) ? \App\Models\Branch::find($data['branch_id'])?->name : null;
            $data['name'] = $this->generatePeriodName(
                $data['cycle_type'],
                $data['start_date'],
                $data['end_date'],
                $branchName
            );
        }

        if ($data['cycle_type'] === 'monthly' || $data['cycle_type'] === 'daily_paid_monthly') {
            $duplicate = PayrollPeriod::where('period', $data['period'])
                ->where('company_id', $data['company_id'])
                ->when(!empty($data['branch_id']), fn ($q) => $q->where('branch_id', $data['branch_id']), fn ($q) => $q->whereNull('branch_id'))
                ->where('cycle_type', $data['cycle_type'])
                ->whereIn('status', ['draft', 'processing'])->exists();
            $labelTipe = $data['cycle_type'] === 'daily_paid_monthly' ? 'harian (bayar bulanan)' : 'bulanan';
            $msg = "Periode payroll {$labelTipe} dengan bulan dan cabang ini sudah ada (belum ditutup).";
        } else {
            $duplicate = PayrollPeriod::where('company_id', $data['company_id'])
                ->when(!empty($data['branch_id']), fn ($q) => $q->where('branch_id', $data['branch_id']), fn ($q) => $q->whereNull('branch_id'))
                ->where('cycle_type', $data['cycle_type'])
                ->where('start_date', $data['start_date'])
                ->where('end_date', $data['end_date'])
                ->whereIn('status', ['draft', 'processing'])->exists();
            $msg = 'Periode payroll dengan rentang tanggal dan cabang ini sudah ada (belum ditutup).';
        }

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => $msg,
                'data' => null,
            ], 422);
        }

        $period = PayrollPeriod::create($data + ['status' => 'draft']);

        return response()->json([
            'success' => true,
            'message' => 'Periode payroll berhasil dibuat',
            'data' => new PayrollPeriodResource($period->load(['company', 'branch'])),
        ], 201);
    }

    public function showPeriod(PayrollPeriod $period): JsonResponse
    {
        $period->load(['company', 'branch', 'runs.employee:id,employee_id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Detail periode payroll',
            'data' => new PayrollPeriodResource($period),
        ]);
    }

    public function updatePeriod(UpdatePayrollPeriodRequest $request, PayrollPeriod $period): JsonResponse
    {
        $period->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Periode payroll berhasil diperbarui',
            'data' => new PayrollPeriodResource($period->fresh()->load(['company', 'branch'])),
        ]);
    }

    public function destroyPeriod(PayrollPeriod $period): JsonResponse
    {
        if ($period->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Periode yang sudah ditutup (closed) tidak dapat dibatalkan atau dihapus.',
                'data' => null,
            ], 422);
        }

        // Soft delete all associated runs and the period itself
        $period->runs()->delete();
        $period->delete();

        return response()->json([
            'success' => true,
            'message' => 'Periode payroll berhasil dibatalkan dan dihapus (soft delete)',
            'data' => null,
        ]);
    }

    public function cancelPeriod(PayrollPeriod $period): JsonResponse
    {
        return $this->destroyPeriod($period);
    }

    // ============ GENERATE PAYSLIP ============
    public function generate(GeneratePayrollRequest $request): JsonResponse
    {
        $period = PayrollPeriod::findOrFail($request->payroll_period_id);

        if ($period->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Periode sudah diproses, tidak bisa generate ulang.',
                'data' => null,
            ], 422);
        }

        // Filter karyawan
        $query = Employee::with('assignments')->where('is_active', true);
        if ($request->has('employee_ids') && is_array($request->employee_ids)) {
            $query->whereIn('id', $request->employee_ids);
        }
        $employees = $query->get();
        if ($employees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada karyawan aktif.',
                'data' => null,
            ], 422);
        }

        DB::transaction(function () use ($employees, $period, $request) {
            // Hapus runs lama beserta detailnya yang tidak termasuk dalam daftar
            $selectedIds = $employees->pluck('id')->toArray();
            $runsToDelete = $period->runs()->whereNotIn('employee_id', $selectedIds)->get();
            foreach ($runsToDelete as $r) {
                $r->details()->delete();
                $r->delete();
            }

            foreach ($employees as $employee) {
                $assignments = $employee->assignments;
                if ($period->company_id) {
                    $assignments = $assignments->filter(function($a) use ($period) {
                        return (string)$a->company_id === (string)$period->company_id
                            || (string)$a->placement_company_id === (string)$period->company_id;
                    });
                }
                if ($period->branch_id) {
                    $assignments = $assignments->where('branch_id', $period->branch_id);
                }
                if ($period->cycle_type) {
                    $assignments = $assignments->filter(function($a) use ($period) {
                        $cycle = $period->cycle_type;
                        if (in_array($cycle, ['weekly_payment', 'weekly_payslip'])) {
                            return in_array($a->salary_cycle_type, ['DAILY_PAID_WEEKLY', 'weekly_payment', 'weekly_payslip', 'weekly']);
                        }
                        if ($cycle === 'daily') {
                            return in_array($a->salary_cycle_type, ['DAILY_PAID_DAILY', 'daily']);
                        }
                        if ($cycle === 'daily_paid_monthly') {
                            return in_array($a->salary_cycle_type, ['DAILY_PAID_MONTHLY', 'daily_paid_monthly']);
                        }
                        if ($cycle === 'monthly') {
                            return in_array($a->salary_cycle_type, ['MONTHLY_PAID_MONTHLY', 'monthly']);
                        }
                        return true;
                    });
                }
                if ($period->start_date && $period->end_date) {
                    $pStartStr = \Carbon\Carbon::parse($period->start_date)->toDateString();
                    $pEndStr   = \Carbon\Carbon::parse($period->end_date)->toDateString();
                    $assignments = $assignments->filter(function($a) use ($pStartStr, $pEndStr) {
                        if ($a->join_date && \Carbon\Carbon::parse($a->join_date)->toDateString() > $pEndStr) {
                            return false;
                        }
                        if ($a->end_date && \Carbon\Carbon::parse($a->end_date)->toDateString() < $pStartStr) {
                            return false;
                        }
                        return true;
                    });
                }

                if ($assignments->isEmpty()) {
                    continue;
                }
                
                foreach ($assignments as $assignment) {
                    $calc = $this->calculator->calculateForAssignment($employee, $assignment, $period);
                    $details = $calc['_details'];
                    unset($calc['_details']);

                    $run = PayrollRun::updateOrCreate(
                        [
                            'payroll_period_id' => $period->id, 
                            'employee_id' => $employee->id,
                            'employee_assignment_id' => $assignment->id
                        ],
                        $calc
                    );

                    // simpan detail komponen
                    $run->details()->delete();
                    foreach ($details as $d) {
                        PayrollDetail::create([
                            'payroll_run_id' => $run->id,
                            'payroll_component_id' => $d['payroll_component_id'],
                            'label' => $d['label'],
                            'type' => $d['type'],
                            'amount' => $d['amount'],
                        ]);
                    }
                }
            }

            $period->update(['status' => 'processing']);
        });

        $period->refresh()->load('runs.employee:id,employee_id,name');

        return response()->json([
            'success' => true,
            'message' => 'Payroll berhasil digenerate',
            'data' => new PayrollPeriodResource($period),
        ], 201);
    }

    // ============ PROCESS / COMPLETE / CLOSE ============
    public function process(PayrollPeriod $period): JsonResponse
    {
        if ($period->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Status periode tidak memungkinkan proses manual.',
                'data' => null,
            ], 422);
        }
        $period->update([
            'status' => 'processing',
            'processed_by' => auth('sanctum')->id(),
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Periode diproses',
            'data' => new PayrollPeriodResource($period->fresh()),
        ]);
    }

    public function complete(PayrollPeriod $period): JsonResponse
    {
        if ($period->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Periode harus berstatus processing terlebih dahulu.',
                'data' => null,
            ], 422);
        }
        $period->update([
            'status' => 'approved',
            'processed_by' => auth('sanctum')->id(),
            'processed_at' => $period->processed_at ?? now(),
        ]);

        // sinkronkan status slip gaji
        $period->runs()->where('status', 'calculated')->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'message' => 'Periode disetujui',
            'data' => new PayrollPeriodResource($period->fresh()),
        ]);
    }

    public function close(PayrollPeriod $period): JsonResponse
    {
        if (!in_array($period->status, ['approved', 'processing', 'paid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Periode tidak dapat ditutup dari status saat ini.',
                'data' => null,
            ], 422);
        }
        $period->update([
            'status' => 'closed',
            'processed_by' => auth('sanctum')->id(),
            'processed_at' => now(),
        ]);

        // sinkronkan status slip gaji menjadi dibayar
        $period->runs()->update(['status' => 'paid']);

        return response()->json([
            'success' => true,
            'message' => 'Periode ditutup',
            'data' => new PayrollPeriodResource($period->fresh()),
        ]);
    }

    // ============ SUMMARY DASHBOARD ============
    public function summary(Request $request): JsonResponse
    {
        $periodValue = $request->input('period'); // YYYY-MM optional

        $query = PayrollRun::query();

        if ($periodValue) {
            $query->whereHas('period', fn ($q) => $q->where('period', $periodValue));
        } else {
            // default: periode terakhir
            $latest = PayrollPeriod::orderByDesc('start_date')->first();
            if ($latest) {
                $query->where('payroll_period_id', $latest->id);
            }
        }

        $summary = (clone $query)->selectRaw(
            'COUNT(DISTINCT employee_id) as total_employees,
             COALESCE(SUM(gross_salary),0) as total_gross,
             COALESCE(SUM(net_salary),0) as total_net,
             COALESCE(SUM(total_tax),0) as total_tax,
             COALESCE(SUM(total_bpjs),0) as total_bpjs'
        )->first();

        $periodCount = PayrollPeriod::count();
        $latestPeriod = PayrollPeriod::orderByDesc('start_date')->first();

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan payroll',
            'data' => [
                'period' => $periodValue ?: ($latestPeriod?->period ?? null),
                'period_id' => $periodValue ? null : $latestPeriod?->id,
                'total_employees' => (int) $summary->total_employees,
                'total_gross' => round((float) $summary->total_gross, 2),
                'total_net' => round((float) $summary->total_net, 2),
                'total_tax' => round((float) $summary->total_tax, 2),
                'total_bpjs' => round((float) $summary->total_bpjs, 2),
                'total_periods' => (int) $periodCount,
                'currency' => \App\Models\PayrollSetting::value('currency', 'IDR'),
            ],
        ]);
    }

    // ============ ADMIN: lihat payslips ============
    public function runs(Request $request): JsonResponse
    {
        $runs = PayrollRun::query()
            ->with(['employee:id,employee_id,name', 'period:id,name,period,company_id,branch_id', 'period.company', 'period.branch', 'assignment.branch'])
            ->withCount('details')
            ->when($request->filled('payroll_period_id'), fn ($q) => $q->where('payroll_period_id', $request->payroll_period_id))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Daftar slip gaji',
            'data' => PayrollRunResource::collection($runs)->response()->getData(true),
        ]);
    }

    public function showRun(PayrollRun $run): JsonResponse
    {
        $run->load([
            'employee:id,employee_id,name,phone,bpjs_kesehatan,bpjs_ketenagakerjaan,npwp,ptkp_status',
            'employee.company.group',
            'employee.branch',
            'employee.department',
            'employee.division',
            'employee.position',
            'assignment.company.group',
            'assignment.branch',
            'assignment.department',
            'assignment.division',
            'assignment.position',
            'period',
            'details',
        ]);

        $extra = $this->getRunExtraData($run);

        $data = (new PayrollRunResource($run))->toArray(request());
        $data['attendance'] = $extra['attendanceSummary'];
        $data['attendance_logs'] = $extra['attendanceLogs'];
        $data['rewards'] = $extra['rewards'];
        $data['deductions'] = $extra['deductions'];
        $data['has_bpjs'] = $extra['componentStatus']['has_bpjs'];
        $data['has_bpjs_kesehatan'] = $extra['componentStatus']['has_bpjs_kesehatan'];
        $data['has_bpjs_ketenagakerjaan'] = $extra['componentStatus']['has_bpjs_ketenagakerjaan'];
        $data['has_tax'] = $extra['componentStatus']['has_tax'];

        // Sinkronisasi bonus inputan ke details jika belum tercatat
        $existingLabels = collect($data['details'] ?? [])->pluck('label')->all();
        $bonusAdded = 0;
        foreach ($extra['rewards'] as $reward) {
            $rTitle = $reward->title ?: ('Bonus (' . ucfirst($reward->type) . ')');
            if (!in_array($rTitle, $existingLabels)) {
                $data['details'][] = [
                    'id' => 'reward-' . $reward->id,
                    'payroll_component_id' => null,
                    'label' => $rTitle,
                    'type' => 'earning',
                    'amount' => (float)$reward->amount,
                ];
                $bonusAdded += (float)$reward->amount;
                $existingLabels[] = $rTitle;
            }
        }
        if ($bonusAdded > 0) {
            $data['gross_salary'] = ($data['gross_salary'] ?? 0) + $bonusAdded;
            $data['net_salary'] = ($data['net_salary'] ?? 0) + $bonusAdded;
        }

        // Sinkronisasi potongan lain-lain ke details jika belum tercatat
        $deductionAdded = 0;
        foreach ($extra['deductions'] as $deduction) {
            $dTitle = $deduction->title ?: ('Potongan (' . ucfirst($deduction->type) . ')');
            if (!in_array($dTitle, $existingLabels)) {
                $data['details'][] = [
                    'id' => 'deduction-' . $deduction->id,
                    'payroll_component_id' => null,
                    'label' => $dTitle,
                    'type' => 'deduction',
                    'amount' => (float)$deduction->amount,
                ];
                $deductionAdded += (float)$deduction->amount;
                $existingLabels[] = $dTitle;
            }
        }
        if ($deductionAdded > 0) {
            $data['total_deduction'] = ($data['total_deduction'] ?? 0) + $deductionAdded;
            $data['net_salary'] = ($data['net_salary'] ?? 0) - $deductionAdded;
        }

        $this->syncRunLateDeduction($run, $data, $extra);

        return response()->json([
            'success' => true,
            'message' => 'Detail slip gaji',
            'data'    => $data,
        ]);
    }

    // ============ EMPLOYEE: PAYSLIPS MILIK SENDIRI ============
    public function myPayslips(Request $request): JsonResponse
    {
        $employee = $request->user();

        $runs = PayrollRun::query()
            ->where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->whereIn('status', ['approved', 'paid'])
                  ->orWhereHas('period', fn ($p) => $p->whereIn('status', ['approved', 'paid', 'closed', 'completed']));
            })
            ->with(['period', 'details'])
            ->when($request->filled('payroll_period_id'), fn ($q) => $q->where('payroll_period_id', $request->payroll_period_id))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Daftar slip gaji saya',
            'data' => PayrollRunResource::collection($runs)->response()->getData(true),
        ]);
    }

    public function myPayslip(PayrollRun $run, Request $request): JsonResponse
    {
        // Hanya slip milik employee yang login
        if ($run->employee_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
                'data' => null,
            ], 403);
        }

        $run->load([
            'period',
            'details',
            'employee.company.group',
            'employee.branch',
            'employee.department',
            'employee.division',
            'employee.position',
            'assignment.company.group',
            'assignment.branch',
            'assignment.department',
            'assignment.division',
            'assignment.position',
        ]);
        $extra = $this->getRunExtraData($run);

        $data = (new PayrollRunResource($run))->toArray(request());
        $data['attendance'] = $extra['attendanceSummary'];
        $data['attendance_logs'] = $extra['attendanceLogs'];
        $data['rewards'] = $extra['rewards'];
        $data['deductions'] = $extra['deductions'];
        $data['has_bpjs'] = $extra['componentStatus']['has_bpjs'];
        $data['has_bpjs_kesehatan'] = $extra['componentStatus']['has_bpjs_kesehatan'];
        $data['has_bpjs_ketenagakerjaan'] = $extra['componentStatus']['has_bpjs_ketenagakerjaan'];
        $data['has_tax'] = $extra['componentStatus']['has_tax'];

        $existingLabels = collect($data['details'] ?? [])->pluck('label')->all();
        $bonusAdded = 0;
        foreach ($extra['rewards'] as $reward) {
            $rTitle = $reward->title ?: ('Bonus (' . ucfirst($reward->type) . ')');
            if (!in_array($rTitle, $existingLabels)) {
                $data['details'][] = [
                    'id' => 'reward-' . $reward->id,
                    'payroll_component_id' => null,
                    'label' => $rTitle,
                    'type' => 'earning',
                    'amount' => (float)$reward->amount,
                ];
                $bonusAdded += (float)$reward->amount;
                $existingLabels[] = $rTitle;
            }
        }
        if ($bonusAdded > 0) {
            $data['gross_salary'] = ($data['gross_salary'] ?? 0) + $bonusAdded;
            $data['net_salary'] = ($data['net_salary'] ?? 0) + $bonusAdded;
        }

        $deductionAdded = 0;
        foreach ($extra['deductions'] as $deduction) {
            $dTitle = $deduction->title ?: ('Potongan (' . ucfirst($deduction->type) . ')');
            if (!in_array($dTitle, $existingLabels)) {
                $data['details'][] = [
                    'id' => 'deduction-' . $deduction->id,
                    'payroll_component_id' => null,
                    'label' => $dTitle,
                    'type' => 'deduction',
                    'amount' => (float)$deduction->amount,
                ];
                $deductionAdded += (float)$deduction->amount;
                $existingLabels[] = $dTitle;
            }
        }
        if ($deductionAdded > 0) {
            $data['total_deduction'] = ($data['total_deduction'] ?? 0) + $deductionAdded;
            $data['net_salary'] = ($data['net_salary'] ?? 0) - $deductionAdded;
        }

        $this->syncRunLateDeduction($run, $data, $extra);

        return response()->json([
            'success' => true,
            'message' => 'Detail slip gaji saya',
            'data' => $data,
        ]);
    }

    public function runPdf(PayrollRun $run)
    {
        $run->load([
            'employee.department',
            'employee.position',
            'employee.company.group',
            'employee.branch',
            'assignment.company.group',
            'assignment.branch',
            'assignment.department',
            'assignment.position',
            'period',
            'details'
        ]);

        $extra = $this->getRunExtraData($run);

        $existingLabels = $run->details->pluck('label')->all();
        foreach ($extra['rewards'] as $reward) {
            $rTitle = $reward->title ?: ('Bonus (' . ucfirst($reward->type) . ')');
            if (!in_array($rTitle, $existingLabels)) {
                $detailModel = new \App\Models\PayrollDetail([
                    'label' => $rTitle,
                    'type' => 'earning',
                    'amount' => (float)$reward->amount,
                ]);
                $run->details->push($detailModel);
                $run->net_salary += (float)$reward->amount;
                $run->gross_salary += (float)$reward->amount;
                $existingLabels[] = $rTitle;
            }
        }

        foreach ($extra['deductions'] as $deduction) {
            $dTitle = $deduction->title ?: ('Potongan (' . ucfirst($deduction->type) . ')');
            if (!in_array($dTitle, $existingLabels)) {
                $detailModel = new \App\Models\PayrollDetail([
                    'label' => $dTitle,
                    'type' => 'deduction',
                    'amount' => (float)$deduction->amount,
                ]);
                $run->details->push($detailModel);
                $run->net_salary -= (float)$deduction->amount;
                $run->total_deduction += (float)$deduction->amount;
                $existingLabels[] = $dTitle;
            }
        }

        $this->syncRunLateDeductionForModel($run, $extra);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payslip', [
            'run' => $run,
            'rewards' => $extra['rewards'],
            'deductions' => $extra['deductions'],
            'attendanceSummary' => $extra['attendanceSummary'],
            'attendanceLogs' => $extra['attendanceLogs'],
            'componentStatus' => $extra['componentStatus'],
        ]);
        
        $filename = 'Slip_Gaji_' . str_replace(' ', '_', $run->employee->name ?? 'Karyawan') . '_' . str_replace(' ', '_', $run->period->name ?? 'Periode') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function myPayslipPdf(PayrollRun $run, Request $request)
    {
        if ($run->employee_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
                'data' => null,
            ], 403);
        }

        $run->load([
            'employee.department',
            'employee.position',
            'employee.company.group',
            'employee.branch',
            'assignment.company.group',
            'assignment.branch',
            'assignment.department',
            'assignment.position',
            'period',
            'details'
        ]);

        $extra = $this->getRunExtraData($run);

        $existingLabels = $run->details->pluck('label')->all();
        foreach ($extra['rewards'] as $reward) {
            $rTitle = $reward->title ?: ('Bonus (' . ucfirst($reward->type) . ')');
            if (!in_array($rTitle, $existingLabels)) {
                $detailModel = new \App\Models\PayrollDetail([
                    'label' => $rTitle,
                    'type' => 'earning',
                    'amount' => (float)$reward->amount,
                ]);
                $run->details->push($detailModel);
                $run->net_salary += (float)$reward->amount;
                $run->gross_salary += (float)$reward->amount;
                $existingLabels[] = $rTitle;
            }
        }

        foreach ($extra['deductions'] as $deduction) {
            $dTitle = $deduction->title ?: ('Potongan (' . ucfirst($deduction->type) . ')');
            if (!in_array($dTitle, $existingLabels)) {
                $detailModel = new \App\Models\PayrollDetail([
                    'label' => $dTitle,
                    'type' => 'deduction',
                    'amount' => (float)$deduction->amount,
                ]);
                $run->details->push($detailModel);
                $run->net_salary -= (float)$deduction->amount;
                $run->total_deduction += (float)$deduction->amount;
                $existingLabels[] = $dTitle;
            }
        }

        $this->syncRunLateDeductionForModel($run, $extra);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payslip', [
            'run' => $run,
            'rewards' => $extra['rewards'],
            'deductions' => $extra['deductions'],
            'attendanceSummary' => $extra['attendanceSummary'],
            'attendanceLogs' => $extra['attendanceLogs'],
            'componentStatus' => $extra['componentStatus'],
        ]);
        
        $filename = 'Slip_Gaji_' . str_replace(' ', '_', $run->period->name ?? 'Periode') . '.pdf';
        
        return $pdf->download($filename);
    }

    private function syncRunLateDeduction(PayrollRun $run, array &$data, array $extra): void
    {
        // Dinonaktifkan sementara (semua potongan keterlambatan di-0-kan) sesuai arahan manajemen
        $dynLateDeduction = 0;

        $foundIndex = -1;
        foreach ($data['details'] as $idx => $dItem) {
            $lbl = strtolower($dItem['label'] ?? '');
            if (str_contains($lbl, 'keterlambatan') || str_contains($lbl, 'terlambat')) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex !== -1) {
            $currAmount = (float)($data['details'][$foundIndex]['amount'] ?? 0);
            if ($currAmount != $dynLateDeduction) {
                $diff = $dynLateDeduction - $currAmount;
                $data['details'][$foundIndex]['amount'] = $dynLateDeduction;
                $data['total_deduction'] = ($data['total_deduction'] ?? 0) + $diff;
                $data['net_salary'] = ($data['net_salary'] ?? 0) - $diff;

                $detailId = $data['details'][$foundIndex]['id'] ?? null;
                if ($detailId && is_numeric($detailId)) {
                    \App\Models\PayrollDetail::where('id', $detailId)->update(['amount' => $dynLateDeduction]);
                    $run->update([
                        'total_deduction' => $run->details()->where('type', 'deduction')->sum('amount'),
                        'net_salary' => $run->gross_salary - $run->details()->where('type', 'deduction')->sum('amount'),
                    ]);
                }
            }
        } elseif ($dynLateDeduction > 0) {
            $lateComp = \App\Models\EmployeeSalaryComponent::where('employee_id', $run->employee_id)
                ->where('is_active', true)
                ->whereHas('component', fn($c) => $c->whereIn('code', ['potongan_terlambat', 'potongan_terlambat_harian']))
                ->with('component')
                ->first();

            if ($lateComp) {
                $newDetail = \App\Models\PayrollDetail::create([
                    'payroll_run_id' => $run->id,
                    'payroll_component_id' => $lateComp->payroll_component_id,
                    'label' => $lateComp->component->name ?? 'Potongan Keterlambatan',
                    'type' => 'deduction',
                    'amount' => $dynLateDeduction,
                ]);
                $data['details'][] = [
                    'id' => $newDetail->id,
                    'payroll_component_id' => $lateComp->payroll_component_id,
                    'label' => $newDetail->label,
                    'type' => 'deduction',
                    'amount' => $dynLateDeduction,
                ];
                $data['total_deduction'] = ($data['total_deduction'] ?? 0) + $dynLateDeduction;
                $data['net_salary'] = ($data['net_salary'] ?? 0) - $dynLateDeduction;
                $run->update([
                    'total_deduction' => $run->details()->where('type', 'deduction')->sum('amount'),
                    'net_salary' => $run->gross_salary - $run->details()->where('type', 'deduction')->sum('amount'),
                ]);
            }
        }
    }

    private function syncRunLateDeductionForModel(PayrollRun $run, array $extra): void
    {
        // Dinonaktifkan sementara (semua potongan keterlambatan di-0-kan) sesuai arahan manajemen
        $dynLateDeduction = 0;
        $foundLate = false;
        foreach ($run->details as $d) {
            $lbl = strtolower($d->label ?? '');
            if (str_contains($lbl, 'keterlambatan') || str_contains($lbl, 'terlambat')) {
                $foundLate = true;
                if ((float)$d->amount != $dynLateDeduction) {
                    $diff = $dynLateDeduction - (float)$d->amount;
                    $d->amount = $dynLateDeduction;
                    $d->save();
                    $run->total_deduction += $diff;
                    $run->net_salary -= $diff;
                    $run->save();
                }
                break;
            }
        }
        if (!$foundLate && $dynLateDeduction > 0) {
            $lateComp = \App\Models\EmployeeSalaryComponent::where('employee_id', $run->employee_id)
                ->where('is_active', true)
                ->whereHas('component', fn($c) => $c->whereIn('code', ['potongan_terlambat', 'potongan_terlambat_harian']))
                ->with('component')
                ->first();
            if ($lateComp) {
                $newDetail = \App\Models\PayrollDetail::create([
                    'payroll_run_id' => $run->id,
                    'payroll_component_id' => $lateComp->payroll_component_id,
                    'label' => $lateComp->component->name ?? 'Potongan Keterlambatan',
                    'type' => 'deduction',
                    'amount' => $dynLateDeduction,
                ]);
                $run->details->push($newDetail);
                $run->total_deduction += $dynLateDeduction;
                $run->net_salary -= $dynLateDeduction;
                $run->save();
            }
        }
    }

    private function getRunExtraData(PayrollRun $run): array
    {
        // 1. Rewards / Bonus dari inputan bonus
        $rewards = \App\Models\Reward::where('employee_id', $run->employee_id)
            ->where(function ($q) use ($run) {
                $q->where('payroll_period_id', $run->payroll_period_id);
                if ($run->period && $run->period->start_date && $run->period->end_date) {
                    $pStart = \Carbon\Carbon::parse($run->period->start_date)->toDateString();
                    $pEnd   = \Carbon\Carbon::parse($run->period->end_date)->toDateString();
                    $q->orWhere(function ($sub) use ($pStart, $pEnd) {
                        $sub->whereNull('payroll_period_id')
                            ->whereBetween('reward_date', [$pStart, $pEnd]);
                    });
                }
            })
            ->get();

        // 1b. Potongan Lain-Lain dari inputan potongan
        $deductions = \App\Models\PayrollDeduction::where('employee_id', $run->employee_id)
            ->where(function ($q) use ($run) {
                $q->where('payroll_period_id', $run->payroll_period_id);
                if ($run->period && $run->period->start_date && $run->period->end_date) {
                    $pStart = \Carbon\Carbon::parse($run->period->start_date)->toDateString();
                    $pEnd   = \Carbon\Carbon::parse($run->period->end_date)->toDateString();
                    $q->orWhere(function ($sub) use ($pStart, $pEnd) {
                        $sub->whereNull('payroll_period_id')
                            ->whereBetween('deduction_date', [$pStart, $pEnd]);
                    });
                }
            })
            ->get();

        // 2. Attendance Summary & Daily Logs (Halaman Absensi)
        $attendanceSummary = null;
        $attendanceLogs = [];

        if ($run->period && $run->period->start_date && $run->period->end_date) {
            $startDate = \Carbon\Carbon::parse($run->period->start_date)->toDateString();
            $endDate   = \Carbon\Carbon::parse($run->period->end_date)->toDateString();

            $empShifts = \App\Models\EmployeeShift::with('shift')
                ->where('employee_id', $run->employee_id)
                ->where('effective_date', '<=', $endDate)
                ->where(function($sq) use ($startDate) {
                    $sq->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                })
                ->where('is_active', true)
                ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderByDesc('effective_date')
                ->get();

            $totalDays = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;

            $attendances = \App\Models\Attendance::with('shift')
                ->where('employee_id', $run->employee_id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->keyBy(fn ($a) => \Carbon\Carbon::parse($a->date)->toDateString());

            $totalValidOvertimeMin = 0;
            foreach ($attendances as $attItem) {
                $totalValidOvertimeMin += $this->calculator->getValidOvertimeMinutes($attItem);
            }

            $periodDays = [];
            $curr = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            $no = 1;

            $calcHadir = 0;
            $calcLibur = 0;
            $calcCuti = 0;
            $calcAlpha = 0;
            $calcTotalLateMinutes = 0;

            while ($curr->lte($end)) {
                $dateStr = $curr->toDateString();
                $attRecord = $attendances->get($dateStr);
                $dayName = $curr->locale('id')->isoFormat('dddd');
                $isWeekend = in_array($curr->dayOfWeekIso, [7]); // Minggu

                $matchingShifts = $empShifts->filter(function($s) use ($dateStr) {
                    $effDate = $s->effective_date instanceof \Carbon\Carbon
                        ? $s->effective_date->toDateString()
                        : substr((string)$s->effective_date, 0, 10);
                    $endDate2 = $s->end_date ? ($s->end_date instanceof \Carbon\Carbon
                        ? $s->end_date->toDateString()
                        : substr((string)$s->end_date, 0, 10)) : null;
                    return $effDate <= $dateStr && (!$endDate2 || $endDate2 >= $dateStr);
                });
                $empShift = $matchingShifts->first(fn($s) => !is_null($s->end_date))
                         ?? $matchingShifts->first();
                $shiftObj = $attRecord?->shift ?? $empShift?->shift;

                if ($attRecord) {
                    $clockIn = $attRecord->clock_in_at ? \Carbon\Carbon::parse($attRecord->clock_in_at)->timezone('Asia/Jakarta')->format('H:i') : '-';
                    $clockOut = $attRecord->clock_out_at ? \Carbon\Carbon::parse($attRecord->clock_out_at)->timezone('Asia/Jakarta')->format('H:i') : '-';
                    $status = $attRecord->status;
                    $shiftName = $shiftObj?->name ?? 'Normal';
                    $lateMin = (int)($attRecord->late_minutes ?? 0);

                    // Recalculate late minutes purely from clock_in time vs shift start time + late tolerance (sinkron dengan AdminAttendanceController)
                    if ($attRecord->clock_in_at && $shiftObj && $shiftObj->clock_in) {
                        $clockInCarbon = \Carbon\Carbon::parse($attRecord->clock_in_at)->timezone('Asia/Jakarta');
                        $clockInTime  = (int) $clockInCarbon->format('H') * 60 + (int) $clockInCarbon->format('i');
                        $shiftInParts = explode(':', $shiftObj->clock_in);
                        $shiftInTime  = (int) ($shiftInParts[0] ?? 0) * 60 + (int) ($shiftInParts[1] ?? 0);
                        $tolerance    = (int) ($shiftObj->late_tolerance_minutes ?? 0);

                        if ($clockInTime > ($shiftInTime + $tolerance)) {
                            $lateMin = $clockInTime - ($shiftInTime + $tolerance);
                        } else {
                            $lateMin = 0;
                        }
                    }

                    if ($lateMin === 0 && $status === 'late') {
                        $status = 'present';
                    }
                    if ($lateMin > 0 && !in_array($status, ['sick', 'leave', 'permit', 'off', '-'])) {
                        $status = 'late';
                    }

                    $overtimeMin = $this->calculator->getValidOvertimeMinutes($attRecord);
                    $workHours = (float)($attRecord->working_hours ?? 0);
                    $notes = $attRecord->notes ?? '';
                } else {
                    $clockIn = '-';
                    $clockOut = '-';
                    $lateMin = 0;
                    $overtimeMin = 0;
                    $workHours = 0;
                    if (!$empShift || !$empShift->shift) {
                        $shiftName = 'Off / Libur';
                        $status = 'off';
                        $notes = 'Jatah Libur';
                    } elseif ($isWeekend) {
                        $shiftName = $empShift->shift?->name ?? 'Normal';
                        $status = 'holiday';
                        $notes = 'Libur Akhir Pekan';
                    } else {
                        $shiftName = $empShift->shift?->name ?? 'Normal';
                        $status = 'absent';
                        $notes = 'Tidak Hadir';
                    }
                }

                if (in_array($status, ['present', 'late', 'early_leave'])) {
                    $calcHadir++;
                } elseif (in_array($status, ['holiday', 'day_off', 'off'])) {
                    $calcLibur++;
                } elseif (in_array($status, ['leave', 'sick', 'permission', 'permit'])) {
                    $calcCuti++;
                } elseif ($status === 'absent') {
                    $calcAlpha++;
                }
                $calcTotalLateMinutes += $lateMin;

                $periodDays[] = [
                    'no' => $no++,
                    'date' => $dateStr,
                    'day_name' => $dayName,
                    'formatted_date' => $curr->format('d/m/Y'),
                    'shift' => $shiftName,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'late_minutes' => $lateMin,
                    'overtime_minutes' => $overtimeMin,
                    'working_hours' => $workHours,
                    'status' => $status,
                    'notes' => $notes,
                ];

                $curr->addDay();
            }

            $grossSalary = $run->gross_salary ?? 0;
            $ratePerDay  = $calcHadir > 0 ? $grossSalary / $totalDays : 0;
            $ratePerHour = $ratePerDay > 0 ? $ratePerDay / 8 : 0;

            $attendanceSummary = [
                'total_period_days' => $totalDays,
                'hadir'             => $calcHadir,
                'libur'             => $calcLibur,
                'cuti'              => $calcCuti,
                'alpha'             => $calcAlpha,
                'work_days'         => $calcHadir,
                'late_minutes'      => $calcTotalLateMinutes,
                'overtime_minutes'  => $totalValidOvertimeMin,
                'rate_per_day'      => round($ratePerDay),
                'rate_per_hour'     => round($ratePerHour),
            ];

            $attendanceLogs = $periodDays;
        }

        // 3. Deteksi apakah karyawan memiliki komponen BPJS dan Pajak aktif
        $empComponents = \App\Models\EmployeeSalaryComponent::with('component')
            ->where('employee_id', $run->employee_id)
            ->where('is_active', true)
            ->get();

        $bpjsKesComp = $empComponents->first(function ($c) {
            $code = strtolower($c->component?->code ?? '');
            $name = strtolower($c->component?->name ?? '');
            return str_contains($code, 'bpjs_kes') || (str_contains($name, 'bpjs') && str_contains($name, 'kesehatan'));
        });

        $bpjsTkComp = $empComponents->first(function ($c) {
            $code = strtolower($c->component?->code ?? '');
            $name = strtolower($c->component?->name ?? '');
            return str_contains($code, 'bpjs_tk') || str_contains($name, 'ketenagakerjaan') || str_contains($name, 'bpjs tk');
        });

        $taxComp = $empComponents->first(function ($c) {
            $code = strtolower($c->component?->code ?? '');
            $name = strtolower($c->component?->name ?? '');
            return str_contains($code, 'pph') || str_contains($code, 'tax') || str_contains($name, 'pajak') || str_contains($name, 'pph');
        });

        // Periksa juga apakah ada rincian BPJS / Pajak di payroll run
        $hasBpjsKesDetail = ((float)($run->total_bpjs ?? 0) > 0) || $run->details->contains(function ($d) {
            $lbl = strtolower($d->label ?? '');
            return ($d->type === 'bpjs' || str_contains($lbl, 'bpjs')) && (str_contains($lbl, 'kes') || !str_contains($lbl, 'tk'));
        });

        $hasBpjsTkDetail = $run->details->contains(function ($d) {
            $lbl = strtolower($d->label ?? '');
            return ($d->type === 'bpjs' || str_contains($lbl, 'bpjs')) && (str_contains($lbl, 'tk') || str_contains($lbl, 'ketenagakerjaan') || str_contains($lbl, 'jht') || str_contains($lbl, 'jp'));
        });

        $hasTaxDetail = ((float)($run->total_tax ?? 0) > 0) || $run->details->contains(function ($d) {
            $lbl = strtolower($d->label ?? '');
            return $d->type === 'tax' || str_contains($lbl, 'pph') || str_contains($lbl, 'pajak');
        });

        $hasBpjsKes = ($bpjsKesComp !== null) || $hasBpjsKesDetail;
        $hasBpjsTk  = ($bpjsTkComp !== null) || $hasBpjsTkDetail;
        $hasTax     = ($taxComp !== null) || $hasTaxDetail;
        $hasBpjs    = $hasBpjsKes || $hasBpjsTk;

        $componentStatus = [
            'has_bpjs' => $hasBpjs,
            'has_bpjs_kesehatan' => $hasBpjsKes,
            'has_bpjs_ketenagakerjaan' => $hasBpjsTk,
            'has_tax' => $hasTax,
        ];

        return [
            'rewards' => $rewards,
            'deductions' => $deductions,
            'attendanceSummary' => $attendanceSummary,
            'attendanceLogs' => $attendanceLogs,
            'componentStatus' => $componentStatus,
        ];
    }

    public function simulate(\App\Models\Employee $employee, Request $request): \Illuminate\Http\JsonResponse
    {
        $period = \App\Models\PayrollPeriod::where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->first();
            
        if (!$period) {
            $period = new \App\Models\PayrollPeriod([
                'name' => 'Simulasi ' . now()->format('F Y'),
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]);
        }
        
        // Allow overriding cycle_type for simulation (useful for weekly_payment vs weekly_payslip)
        if ($request->filled('cycle_type')) {
            $period->cycle_type = $request->input('cycle_type');
        }
        
        $assignmentId = $request->input('assignment_id');
        if ($assignmentId) {
            $assignment = $employee->assignments()->find($assignmentId);
        } else {
            $assignment = $employee->assignments()->where('is_primary', true)->first() ?? $employee->assignments()->first();
        }
        $assignment = $assignment ?? new \App\Models\EmployeeAssignment();
        
        $simulationParams = [
            'working_days' => (int) $request->input('working_days', 22),
            'present_days' => (int) $request->input('present_days', 22),
            'absent_days' => (int) $request->input('absent_days', 0),
            'late_minutes' => (int) $request->input('late_minutes', 0),
            'late_occurrences' => (int) $request->input('late_occurrences', 0),
            'overtime_hours' => (float) $request->input('overtime_hours', 0),
            'days_in_month' => (int) $request->input('days_in_month', 30),
            'day_off' => (int) $request->input('day_off', 4),
        ];

        // 1. MAIN RUN
        $calc = $this->calculator->calculateForAssignment($employee, $assignment, $period, $simulationParams, false);
        
        return response()->json([
            'success' => true,
            'data' => $calc,
            'message' => 'Simulasi berhasil'
        ]);
    }

    // ============ MONITORING / AUDIT PENGGAJIAN KARYAWAN ============
    public function monitoringControl(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id');
        $departmentId = $request->input('department_id');
        $payrollPeriodId = $request->input('payroll_period_id');
        $cycleType = $request->input('cycle_type', 'monthly'); // 'monthly' or 'weekly'
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        $periodStr = sprintf('%04d-%02d', $year, $month);

        // Fetch available periods for this month/year and cycle type
        $availablePeriodsQuery = PayrollPeriod::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($year && $month, fn ($q) => $q->where('period', $periodStr));

        if ($cycleType === 'weekly' || $cycleType === 'weekly_payment') {
            $availablePeriodsQuery->where('cycle_type', 'weekly_payment');
        } elseif ($cycleType === 'monthly') {
            $availablePeriodsQuery->where(function ($sub) {
                $sub->where('cycle_type', 'monthly')->orWhereNull('cycle_type');
            });
        }
        $availablePeriods = $availablePeriodsQuery->orderByDesc('start_date')->get();

        // Determine which period(s) to check
        $periodObj = null;
        if ($payrollPeriodId && $payrollPeriodId !== 'all') {
            $periodObj = PayrollPeriod::find($payrollPeriodId);
            $targetPeriodIds = [$periodObj->id];
        } elseif ($payrollPeriodId === 'all') {
            $targetPeriodIds = $availablePeriods->pluck('id')->toArray();
        } else {
            $periodObj = $availablePeriods->first();
            $targetPeriodIds = $periodObj ? [$periodObj->id] : [];
            $payrollPeriodId = $periodObj?->id;
        }

        // Get active employees matching filters
        $employeeQuery = Employee::query()
            ->with([
                'primaryAssignment.company',
                'primaryAssignment.branch',
                'primaryAssignment.department',
                'primaryAssignment.position',
                'assignments.company',
                'assignments.branch',
                'assignments.department',
                'assignments.position'
            ])
            ->where('is_active', true);

        if ($companyId) {
            $employeeQuery->whereHas('assignments', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->orWhere('placement_company_id', $companyId);
            });
        }
        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $employeeQuery->whereHas('assignments', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }
        if ($departmentId) {
            $employeeQuery->whereHas('assignments', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        // Filter by salary cycle: weekly vs monthly
        if ($cycleType === 'weekly' || $cycleType === 'weekly_payment') {
            $employeeQuery->whereHas('assignments', function ($q) {
                $q->whereIn('salary_cycle_type', ['DAILY_PAID_WEEKLY', 'weekly']);
            });
        } elseif ($cycleType === 'monthly') {
            $employeeQuery->whereDoesntHave('assignments', function ($q) {
                $q->whereIn('salary_cycle_type', ['DAILY_PAID_WEEKLY', 'weekly']);
            });
        }

        $employees = $employeeQuery->get();

        // Get existing payroll runs for target period(s)
        $runs = !empty($targetPeriodIds)
            ? PayrollRun::query()
                ->whereIn('payroll_period_id', $targetPeriodIds)
                ->get()
                ->keyBy('employee_id')
            : collect();

        $resultData = [];
        $totalProcessed = 0;
        $totalUnprocessed = 0;
        $totalEmailSent = 0;
        $totalEmailUnsent = 0;

        foreach ($employees as $emp) {
            $run = $runs->get($emp->id);
            $assignment = $emp->primaryAssignment ?? $emp->assignments->first();
            
            $isProcessed = !is_null($run);
            if ($isProcessed) {
                $totalProcessed++;
            } else {
                $totalUnprocessed++;
            }

            $isEmailSent = $run && !is_null($run->email_sent_at);
            if ($isEmailSent) {
                $totalEmailSent++;
            } else {
                $totalEmailUnsent++;
            }

            $rawCycle = $assignment?->salary_cycle_type;
            $cycleLabel = match ($rawCycle) {
                'DAILY_PAID_WEEKLY', 'weekly' => 'Mingguan',
                'DAILY_PAID_MONTHLY' => 'Bulanan (Harian)',
                'MONTHLY_PAID_MONTHLY' => 'Bulanan (Tetap)',
                default => 'Bulanan',
            };

            $resultData[] = [
                'employee_id' => $emp->id,
                'nip' => $emp->nip ?? $emp->employee_id,
                'name' => $emp->name,
                'email' => $emp->email,
                'company_name' => $assignment?->company?->name ?? '-',
                'branch_name' => $assignment?->branch?->name ?? '-',
                'department_name' => $assignment?->department?->name ?? '-',
                'position_name' => $assignment?->position?->name ?? '-',
                'salary_cycle_type' => $rawCycle,
                'salary_cycle_label' => $cycleLabel,
                'period_name' => $periodObj ? $periodObj->name : ($availablePeriods->isNotEmpty() ? $availablePeriods->first()->name : 'Periode ' . $periodStr),
                'period_id' => $payrollPeriodId,
                'is_payroll_processed' => $isProcessed,
                'payroll_run_id' => $run?->id,
                'payroll_status' => $run ? ($run->status ?? 'calculated') : 'unprocessed',
                'net_salary' => $run ? (float)$run->net_salary : 0,
                'calculated_at' => $run?->calculated_at?->format('Y-m-d H:i:s'),
                'is_email_sent' => $isEmailSent,
                'email_sent_at' => $run?->email_sent_at?->format('Y-m-d H:i:s'),
            ];
        }

        // Apply status filter if provided
        if ($request->filled('status')) {
            $filterStatus = $request->status;
            if ($filterStatus === 'processed') {
                $resultData = array_filter($resultData, fn($item) => $item['is_payroll_processed']);
            } elseif ($filterStatus === 'unprocessed') {
                $resultData = array_filter($resultData, fn($item) => !$item['is_payroll_processed']);
            }
        }

        if ($request->filled('email_status')) {
            $filterEmailStatus = $request->email_status;
            if ($filterEmailStatus === 'sent') {
                $resultData = array_filter($resultData, fn($item) => $item['is_email_sent']);
            } elseif ($filterEmailStatus === 'unsent') {
                $resultData = array_filter($resultData, fn($item) => !$item['is_email_sent']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Monitoring status penggajian karyawan',
            'summary' => [
                'total_employees' => count($employees),
                'total_processed' => $totalProcessed,
                'total_unprocessed' => $totalUnprocessed,
                'total_email_sent' => $totalEmailSent,
                'total_email_unsent' => $totalEmailUnsent,
                'period_name' => $periodObj ? $periodObj->name : ($availablePeriods->isNotEmpty() ? $availablePeriods->first()->name : 'Periode ' . $periodStr),
                'period_id' => $payrollPeriodId,
            ],
            'available_periods' => $availablePeriods->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'cycle_type' => $p->cycle_type,
                'start_date' => $p->start_date?->format('Y-m-d'),
                'end_date' => $p->end_date?->format('Y-m-d'),
                'status' => $p->status,
            ]),
            'data' => array_values($resultData),
        ]);
    }

    public function sendPayslipEmail(PayrollRun $run): JsonResponse
    {
        $run->load(['employee', 'period', 'details', 'assignment']);

        if (!$run->employee || !filter_var($run->employee->email ?? '', FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Karyawan tidak memiliki email valid.'], 422);
        }

        $result = $this->mailer->sendPayslip($run);

        if ($result['success']) {
            $run->update([
                'email_sent_at' => now(),
                'email_sent_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['success'] ? [
                'payroll_run_id' => $run->id,
                'email_sent_at' => $run->fresh()->email_sent_at?->format('Y-m-d H:i:s'),
            ] : null,
        ], $result['success'] ? 200 : 422);
    }

    public function sendBulkPayslipEmail(Request $request): JsonResponse
    {
        $request->validate([
            'payroll_period_id' => 'required|exists:payroll_periods,id',
        ]);

        $runs = PayrollRun::with(['employee', 'period', 'details', 'assignment'])
            ->where('payroll_period_id', $request->payroll_period_id)
            ->whereNull('email_sent_at')
            ->get();

        if ($runs->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua slip gaji pada periode ini sudah pernah dikirim.',
                'data'    => ['sent_count' => 0, 'failed_count' => 0, 'errors' => []],
            ]);
        }

        $result = $this->mailer->sendBulkPayslip($runs->all());

        // Update email_sent_at untuk yang berhasil
        $sentAt = now();
        $sentBy = auth()->id();
        foreach ($runs as $run) {
            if ($run->employee && filter_var($run->employee->email ?? '', FILTER_VALIDATE_EMAIL)) {
                $run->update(['email_sent_at' => $sentAt, 'email_sent_by' => $sentBy]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$result['sent']} slip gaji berhasil dikirim, {$result['failed']} gagal.",
            'data'    => [
                'sent_count'   => $result['sent'],
                'failed_count' => $result['failed'],
                'errors'       => $result['errors'],
            ],
        ]);
    }

    // ─── AUTO-GENERATE PERIODE BERDASARKAN CYCLE_TYPE & TANGGAL ───────────────
    /**
     * Endpoint: POST /payroll/periods/auto-generate
     *
     * Body:
     *   cycle_type    : daily | weekly_payment | weekly_payslip | monthly
     *   company_id    : int
     *   reference_date: YYYY-MM-DD (default: today)
     *
     * Untuk weekly_payment / weekly_payslip:
     *   start_date & end_date harus dikirim manual (7 hari)
     *
     * Langsung mengembalikan PayrollPeriod yang baru dibuat.
     */
    public function autoGeneratePeriod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cycle_type'     => 'required|in:daily,weekly_payment,weekly_payslip,monthly',
            'company_id'     => 'required|exists:companies,id',
            'branch_id'      => 'nullable|exists:branches,id',
            'reference_date' => 'nullable|date',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
        ]);

        $ref = Carbon::parse($data['reference_date'] ?? now());
        $cycleType = $data['cycle_type'];
        $companyId = $data['company_id'];
        $branchId  = $data['branch_id'] ?? null;

        switch ($cycleType) {
            case 'daily':
                $startDate = $data['start_date'] ?? $ref->toDateString();
                $endDate   = $startDate;
                $period    = Carbon::parse($startDate)->format('Y-m');
                break;

            case 'weekly_payment':
                // Pekan penggajian mingguan: Jumat s/d Kamis (7 hari)
                if (!empty($data['start_date'])) {
                    $startDate = $data['start_date'];
                    $endDate   = $data['end_date'] ?? Carbon::parse($startDate)->addDays(6)->toDateString();
                } else {
                    $startCarbon = $ref->isFriday() ? $ref->copy() : $ref->copy()->previous(Carbon::FRIDAY);
                    $startDate = $startCarbon->toDateString();
                    $endDate   = $data['end_date'] ?? $startCarbon->copy()->addDays(6)->toDateString();
                }
                $period    = Carbon::parse($startDate)->format('Y-m');
                break;

            case 'weekly_payslip':
                // Tunjangan bulanan karyawan skema mingguan (1 bulan penuh)
                $startDate = $data['start_date'] ?? $ref->copy()->startOfMonth()->toDateString();
                $endDate   = $data['end_date']   ?? $ref->copy()->endOfMonth()->toDateString();
                $period    = Carbon::parse($startDate)->format('Y-m');
                break;

            case 'monthly':
            default:
                $startDate = $data['start_date'] ?? $ref->copy()->startOfMonth()->toDateString();
                $endDate   = $data['end_date']   ?? $ref->copy()->endOfMonth()->toDateString();
                $period    = Carbon::parse($startDate)->format('Y-m');
                break;
        }

        // Cek duplikat
        $existing = PayrollPeriod::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId), fn ($q) => $q->whereNull('branch_id'))
            ->where('cycle_type', $cycleType)
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->whereIn('status', ['draft', 'processing', 'approved'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Periode dengan rentang tanggal dan tipe ini sudah ada.',
                'data'    => new PayrollPeriodResource($existing),
            ], 422);
        }

        $branchName = $branchId ? \App\Models\Branch::find($branchId)?->name : null;
        $name = $this->generatePeriodName($cycleType, $startDate, $endDate, $branchName);

        $payrollPeriod = PayrollPeriod::create([
            'name'       => $name,
            'period'     => $period,
            'cycle_type' => $cycleType,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'company_id' => $companyId,
            'branch_id'  => $branchId,
            'status'     => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Periode '{$name}' berhasil dibuat.",
            'data'    => new PayrollPeriodResource($payrollPeriod),
        ], 201);
    }

    /**
     * Export Excel Report Payroll (Gaji & Bonus Mekanik / Karyawan)
     */
    public function exportExcelReport(Request $request, $period = null)
    {
        $periodId = $period instanceof PayrollPeriod ? $period->id : ($period ?? $request->input('payroll_period_id'));
        $payrollPeriod = $periodId ? PayrollPeriod::with(['branch', 'company'])->find($periodId) : null;

        if (!$payrollPeriod) {
            return response()->json(['message' => 'Periode payroll tidak ditemukan.'], 404);
        }

        $export = new PayrollReportExport($payrollPeriod);
        return $export->download();
    }
}

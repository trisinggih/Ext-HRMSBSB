<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\LoanRequest;
use App\Models\PayrollRun;
use App\Models\KpiAssessment;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function indicators(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $companyId = $request->company_id;
        $branchId = $request->branch_id;

        // Build list of filtered employee IDs if company or branch filter is applied
        $filteredEmployeeIds = null;
        if ($companyId || $branchId) {
            $filteredEmployeeIds = EmployeeAssignment::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->pluck('employee_id')
                ->toArray();
        }

        // 1. Total Active Employees
        $empQuery = Employee::withoutExecutives();
        if ($filteredEmployeeIds !== null) {
            $empQuery->whereIn('id', $filteredEmployeeIds);
        }
        $totalEmployees = $empQuery->count();

        // 2. Present Today & Late Today
        $presentToday = 0;
        $lateToday = 0;

        if (Schema::hasTable('attendance')) {
            $attQuery = DB::table('attendance')->whereDate('date', $today)->whereNotNull('clock_in_at');
            if ($filteredEmployeeIds !== null) {
                $attQuery->whereIn('employee_id', $filteredEmployeeIds);
            }
            $presentToday = $attQuery->count();

            $lateQuery = DB::table('attendance')->whereDate('date', $today)->whereNotNull('clock_in_at')->where('late_minutes', '>', 0);
            if ($filteredEmployeeIds !== null) {
                $lateQuery->whereIn('employee_id', $filteredEmployeeIds);
            }
            $lateToday = $lateQuery->count();
        } elseif (Schema::hasTable('attendances')) {
            $attQuery = DB::table('attendances')->whereDate('date', $today)->whereNotNull('check_in');
            if ($filteredEmployeeIds !== null) {
                $attQuery->whereIn('employee_id', $filteredEmployeeIds);
            }
            $presentToday = $attQuery->count();
        }

        $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
        $lateRate = $presentToday > 0 ? round(($lateToday / $presentToday) * 100) : 0;

        // 3. On Leave & Pending Approvals
        $onLeave = 0;
        $pendingLeave = 0;
        $pendingOvertime = 0;
        $pendingLoan = 0;

        if (Schema::hasTable('leave_requests')) {
            $leaveQuery = DB::table('leave_requests')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->where('status', 'approved');
            if ($filteredEmployeeIds !== null) {
                $leaveQuery->whereIn('employee_id', $filteredEmployeeIds);
            }
            $onLeave = $leaveQuery->count();

            $pLeaveQuery = DB::table('leave_requests')->where('status', 'pending');
            if ($filteredEmployeeIds !== null) {
                $pLeaveQuery->whereIn('employee_id', $filteredEmployeeIds);
            }
            $pendingLeave = $pLeaveQuery->count();
        }

        if (Schema::hasTable('overtime_requests')) {
            $pOvtQuery = DB::table('overtime_requests')->where('status', 'pending');
            if ($filteredEmployeeIds !== null) {
                $pOvtQuery->whereIn('employee_id', $filteredEmployeeIds);
            }
            $pendingOvertime = $pOvtQuery->count();
        }

        if (Schema::hasTable('loan_requests')) {
            $pLoanQuery = DB::table('loan_requests')->where('status', 'pending');
            if ($filteredEmployeeIds !== null) {
                $pLoanQuery->whereIn('employee_id', $filteredEmployeeIds);
            }
            $pendingLoan = $pLoanQuery->count();
        }

        $totalPendingApproval = $pendingLeave + $pendingOvertime + $pendingLoan;

        // 4. Payroll Run Summary (Latest Run / Period)
        $latestPayrollTitle = 'Payroll Belum Ada';
        $latestPayrollStatus = 'DRAFT';
        $latestPayrollTotalNet = 0;

        if (Schema::hasTable('payroll_runs')) {
            $pRunQuery = PayrollRun::query()->with('period')->orderBy('created_at', 'desc');
            if ($filteredEmployeeIds !== null) {
                $pRunQuery->whereIn('employee_id', $filteredEmployeeIds);
            } elseif ($companyId) {
                $pRunQuery->whereHas('period', fn($q) => $q->where('company_id', $companyId));
            }
            $latestRun = $pRunQuery->first();

            if ($latestRun) {
                $periodObj = $latestRun->period;
                $latestPayrollTitle = $periodObj ? ('Payroll Period: ' . $periodObj->name) : 'Payroll Run';
                $latestPayrollStatus = strtoupper($periodObj->status ?? $latestRun->status ?? 'DRAFT');

                $sumQuery = PayrollRun::query();
                if ($latestRun->payroll_period_id) {
                    $sumQuery->where('payroll_period_id', $latestRun->payroll_period_id);
                }
                if ($filteredEmployeeIds !== null) {
                    $sumQuery->whereIn('employee_id', $filteredEmployeeIds);
                }
                $latestPayrollTotalNet = (float) $sumQuery->sum('net_salary');
            }
        }

        // 5. KPI Average Score
        $avgKpiScore = 0;
        if (Schema::hasTable('kpi_assessments')) {
            $kpiQuery = KpiAssessment::query()->whereNotNull('final_score');
            if ($filteredEmployeeIds !== null) {
                $kpiQuery->whereIn('employee_id', $filteredEmployeeIds);
            }
            $avgKpiScore = round($kpiQuery->avg('final_score') ?? 0, 1);
        }

        // Indicators Bar Data
        $indicators = [
            [
                'label' => 'Total Karyawan',
                'value' => number_format($totalEmployees),
                'trendUp' => true,
                'trendText' => 'Aktif Terdaftar',
                'badge' => 'Employees'
            ],
            [
                'label' => 'Hadir Hari Ini',
                'value' => number_format($presentToday),
                'trendUp' => $attendanceRate >= 80,
                'trendText' => $attendanceRate . '% Tingkat Kehadiran',
                'badge' => 'Present'
            ],
            [
                'label' => 'Terlambat Hari Ini',
                'value' => number_format($lateToday),
                'trendUp' => $lateToday == 0,
                'trendText' => $lateRate . '% Dari Total Hadir',
                'badge' => 'Late'
            ],
            [
                'label' => 'Sedang Cuti',
                'value' => number_format($onLeave),
                'trendUp' => true,
                'trendText' => 'Izin / Cuti Disetujui',
                'badge' => 'On Leave'
            ],
            [
                'label' => 'Pending Approval',
                'value' => number_format($totalPendingApproval),
                'trendUp' => $totalPendingApproval == 0,
                'trendText' => $pendingLeave . ' Cuti, ' . $pendingOvertime . ' Lembur, ' . $pendingLoan . ' Kasbon',
                'badge' => 'Approval'
            ]
        ];

        // STATISTICS

        // A. Kehadiran 7 Hari Terakhir
        $attendanceLabels = [];
        $attendanceHadir = [];
        $attendanceTerlambat = [];
        $attendanceCuti = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $attendanceLabels[] = $date->translatedFormat('l');
            $h = 0;
            $t = 0;
            $c = 0;

            if (Schema::hasTable('attendance')) {
                $qH = DB::table('attendance')->whereDate('date', $date->toDateString())->whereNotNull('clock_in_at');
                if ($filteredEmployeeIds !== null) $qH->whereIn('employee_id', $filteredEmployeeIds);
                $h = $qH->count();

                $qT = DB::table('attendance')->whereDate('date', $date->toDateString())->whereNotNull('clock_in_at')->where('late_minutes', '>', 0);
                if ($filteredEmployeeIds !== null) $qT->whereIn('employee_id', $filteredEmployeeIds);
                $t = $qT->count();
            }

            if (Schema::hasTable('leave_requests')) {
                $qC = DB::table('leave_requests')
                    ->whereDate('start_date', '<=', $date->toDateString())
                    ->whereDate('end_date', '>=', $date->toDateString())
                    ->where('status', 'approved');
                if ($filteredEmployeeIds !== null) $qC->whereIn('employee_id', $filteredEmployeeIds);
                $c = $qC->count();
            }

            $attendanceHadir[] = $h;
            $attendanceTerlambat[] = $t;
            $attendanceCuti[] = $c;
        }

        // B. Department Distribution
        $deptLabels = [];
        $deptSeries = [];

        if (Schema::hasTable('departments')) {
            $deptQuery = DB::table('departments')
                ->leftJoin('employee_assignments', 'departments.id', '=', 'employee_assignments.department_id')
                ->select('departments.name as dept_name', DB::raw('count(distinct employee_assignments.employee_id) as total'))
                ->groupBy('departments.id', 'departments.name')
                ->orderBy('total', 'desc');

            if ($companyId) {
                $deptQuery->where('employee_assignments.company_id', $companyId);
            }
            if ($branchId) {
                $deptQuery->where('employee_assignments.branch_id', $branchId);
            }

            $departments = $deptQuery->get();
            foreach ($departments as $dept) {
                if ($dept->total > 0) {
                    $deptLabels[] = $dept->dept_name;
                    $deptSeries[] = (int) $dept->total;
                }
            }
        }

        if (empty($deptLabels)) {
            $deptLabels = ['Umum / Staff'];
            $deptSeries = [$totalEmployees ?: 1];
        }

        // C. Monthly Join vs Resign Trend
        $trendLabels = [];
        $trendMasuk = [];
        $trendKeluar = [];
        for ($i = 1; $i <= Carbon::today()->month; $i++) {
            $trendLabels[] = Carbon::create()->month($i)->translatedFormat('M');

            $mQuery = EmployeeAssignment::whereMonth('join_date', $i)
                ->whereYear('join_date', Carbon::today()->year);
            if ($companyId) $mQuery->where('company_id', $companyId);
            if ($branchId) $mQuery->where('branch_id', $branchId);
            $trendMasuk[] = $mQuery->distinct('employee_id')->count('employee_id');

            $kQuery = Employee::where('is_active', 0)->whereMonth('updated_at', $i)->whereYear('updated_at', Carbon::today()->year);
            if ($filteredEmployeeIds !== null) $kQuery->whereIn('id', $filteredEmployeeIds);
            $trendKeluar[] = $kQuery->count();
        }

        // D. Monthly Request Transactions Trend (Cuti vs Lembur vs Kasbon)
        $txMonthLabels = [];
        $txCuti = [];
        $txLembur = [];
        $txKasbon = [];
        for ($i = 1; $i <= Carbon::today()->month; $i++) {
            $txMonthLabels[] = Carbon::create()->month($i)->translatedFormat('M');

            $qC = LeaveRequest::whereMonth('start_date', $i)->whereYear('start_date', Carbon::today()->year);
            if ($filteredEmployeeIds !== null) $qC->whereIn('employee_id', $filteredEmployeeIds);
            $txCuti[] = $qC->count();

            $qL = OvertimeRequest::whereMonth('date', $i)->whereYear('date', Carbon::today()->year);
            if ($filteredEmployeeIds !== null) $qL->whereIn('employee_id', $filteredEmployeeIds);
            $txLembur[] = $qL->count();

            $qK = LoanRequest::whereMonth('date', $i)->whereYear('date', Carbon::today()->year);
            if ($filteredEmployeeIds !== null) $qK->whereIn('employee_id', $filteredEmployeeIds);
            $txKasbon[] = $qK->count();
        }

        // RECENT TRANSACTIONS (Pengajuan Terakhir)
        $recentTransactions = [];

        if (Schema::hasTable('leave_requests')) {
            $leaves = LeaveRequest::with(['employee'])
                ->when($filteredEmployeeIds !== null, fn($q) => $q->whereIn('employee_id', $filteredEmployeeIds))
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            foreach ($leaves as $l) {
                $recentTransactions[] = [
                    'id' => 'LEAVE-' . $l->id,
                    'employee_name' => $l->employee?->name ?? 'N/A',
                    'type' => 'Pengajuan Cuti (' . ($l->leave_type ?? 'Cuti') . ')',
                    'date' => $l->start_date ? Carbon::parse($l->start_date)->format('d M Y') : '-',
                    'status' => $l->status ?? 'pending',
                    'created_at' => $l->created_at ? $l->created_at->format('Y-m-d H:i') : ''
                ];
            }
        }

        if (Schema::hasTable('overtime_requests')) {
            $overtimes = OvertimeRequest::with(['employee'])
                ->when($filteredEmployeeIds !== null, fn($q) => $q->whereIn('employee_id', $filteredEmployeeIds))
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            foreach ($overtimes as $o) {
                $recentTransactions[] = [
                    'id' => 'OVT-' . $o->id,
                    'employee_name' => $o->employee?->name ?? 'N/A',
                    'type' => 'Pengajuan Lembur (' . ($o->duration_hours ?? 0) . ' Jam)',
                    'date' => $o->date ? Carbon::parse($o->date)->format('d M Y') : '-',
                    'status' => $o->status ?? 'pending',
                    'created_at' => $o->created_at ? $o->created_at->format('Y-m-d H:i') : ''
                ];
            }
        }

        if (Schema::hasTable('loan_requests')) {
            $loans = LoanRequest::with(['employee'])
                ->when($filteredEmployeeIds !== null, fn($q) => $q->whereIn('employee_id', $filteredEmployeeIds))
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            foreach ($loans as $loan) {
                $recentTransactions[] = [
                    'id' => 'LOAN-' . $loan->id,
                    'employee_name' => $loan->employee?->name ?? 'N/A',
                    'type' => 'Pengajuan Kasbon (Rp ' . number_format((float)($loan->amount ?? 0), 0, ',', '.') . ')',
                    'date' => $loan->date ? Carbon::parse($loan->date)->format('d M Y') : ($loan->created_at ? $loan->created_at->format('d M Y') : '-'),
                    'status' => $loan->status ?? 'pending',
                    'created_at' => $loan->created_at ? $loan->created_at->format('Y-m-d H:i') : ''
                ];
            }
        }

        // Sort combined recent transactions by created_at desc
        usort($recentTransactions, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        $recentTransactions = array_slice($recentTransactions, 0, 7);

        $user = $request->user();
        $isStaffHr = ($user instanceof Employee && $user->web_role === 'staff_hr');

        // TOP KPI PERFORMERS (Hanya untuk Admin / Manager / SPV)
        $topPerformers = [];
        if (!$isStaffHr && Schema::hasTable('kpi_assessments')) {
            $topPerformers = KpiAssessment::with(['employee.primaryAssignment.department', 'employee.primaryAssignment.position'])
                ->when($filteredEmployeeIds !== null, fn($q) => $q->whereIn('employee_id', $filteredEmployeeIds))
                ->whereNotNull('final_score')
                ->orderBy('final_score', 'desc')
                ->take(5)
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->employee?->name ?? 'N/A',
                    'department' => $a->employee?->primaryAssignment?->department?->name ?? '-',
                    'position' => $a->employee?->primaryAssignment?->position?->name ?? '-',
                    'score' => round((float)$a->final_score, 1)
                ]);
        }

        $statistics = [
            'attendance' => [
                'categories' => $attendanceLabels,
                'series' => [
                    ['name' => 'Hadir', 'data' => $attendanceHadir],
                    ['name' => 'Terlambat', 'data' => $attendanceTerlambat],
                    ['name' => 'Cuti/Absen', 'data' => $attendanceCuti]
                ]
            ],
            'departments' => [
                'labels' => $deptLabels,
                'series' => $deptSeries
            ],
            'trends' => [
                'categories' => $trendLabels,
                'series' => [
                    ['name' => 'Karyawan Masuk', 'data' => $trendMasuk],
                    ['name' => 'Karyawan Keluar', 'data' => $trendKeluar]
                ]
            ],
            'transaction_trends' => [
                'categories' => $txMonthLabels,
                'series' => [
                    ['name' => 'Cuti/Izin', 'data' => $txCuti],
                    ['name' => 'Lembur', 'data' => $txLembur],
                    ['name' => 'Kasbon', 'data' => $txKasbon]
                ]
            ],
            'kpi' => $isStaffHr ? null : $avgKpiScore
        ];

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data',
            'data' => [
                'indicators' => $indicators,
                'statistics' => $statistics,
                'payroll_summary' => $isStaffHr ? null : [
                    'title' => $latestPayrollTitle,
                    'total_take_home' => $latestPayrollTotalNet,
                    'status' => $latestPayrollStatus,
                ],
                'recent_transactions' => $recentTransactions,
                'top_performers' => $topPerformers,
            ]
        ]);
    }
}

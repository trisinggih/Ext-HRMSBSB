<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\PayrollRun;
use App\Models\LeaveRequest;
use App\Models\EmployeeLoan;
use App\Models\OvertimeRequest;
use App\Models\KpiAssessment;
use App\Models\EmployeeWarning;
use App\Models\EmployeeMutation;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    // 1. Laporan Data Karyawan
    public function employees(Request $request)
    {
        $query = Employee::query()
            ->when(!$request->boolean('include_executives') && !$request->boolean('for_payroll'), fn ($q) => $q->withoutExecutives())
            ->with(['assignments.department', 'assignments.position']);

        if ($request->filled('status')) {
            $query->whereHas('assignments', fn($sq) => $sq->where('employment_status', $request->status));
        }
        if ($request->filled('department_id')) {
            $query->whereHas('assignments', fn($q) => $q->where('department_id', $request->department_id));
        }

        $data = $query->get();
        return response()->json(['data' => $data]);
    }

    // 2. Laporan Per Individu (360 Degree Employee Summary)
    public function individual(Request $request)
    {
        $employeeId = $request->employee_id;
        if (!$employeeId) {
            $firstEmp = Employee::first();
            $employeeId = $firstEmp?->id;
        }

        $employee = Employee::with([
            'assignments.department',
            'assignments.position',
            'assignments.division',
            'assignments.company',
            'salaryComponents'
        ])->find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $startDate = $request->start_date ?? Carbon::today()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfMonth()->toDateString();

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $leaves = LeaveRequest::with('leaveType')
            ->where('employee_id', $employeeId)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->get();

        $overtimes = OvertimeRequest::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $loans = EmployeeLoan::where('employee_id', $employeeId)->get();

        $warnings = EmployeeWarning::where('employee_id', $employeeId)->get();

        $kpis = KpiAssessment::with('period')
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($employee->isExecutive()) {
            $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $attendanceSummary = [
                'total_present' => (int)$periodDays,
                'total_late' => 0,
                'total_late_minutes' => 0,
                'total_absent' => 0,
                'total_sick' => 0,
                'total_permit' => 0,
            ];
        } else {
            $attendanceSummary = [
                'total_present' => $attendances->whereIn('status', ['present', 'hadir'])->count(),
                'total_late' => $attendances->where('late_minutes', '>', 0)->count(),
                'total_late_minutes' => $attendances->sum('late_minutes'),
                'total_absent' => $attendances->where('status', 'absent')->count(),
                'total_sick' => $attendances->where('status', 'sick')->count(),
                'total_permit' => $attendances->where('status', 'permit')->count(),
            ];
        }

        return response()->json([
            'data' => [
                'employee' => $employee,
                'attendance_summary' => $attendanceSummary,
                'attendances' => $attendances,
                'leaves' => $leaves,
                'overtimes' => $overtimes,
                'loans' => $loans,
                'warnings' => $warnings,
                'kpis' => $kpis,
            ]
        ]);
    }

    // 3. Laporan Kehadiran
    public function attendance(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->toDateString();
        $departmentId = $request->department_id;

        $employees = Employee::query()
            ->when(!$request->boolean('include_executives') && !$request->boolean('for_payroll'), fn ($q) => $q->withoutExecutives())
            ->with('assignments.department')
            ->when($departmentId, function ($q) use ($departmentId) {
                return $q->whereHas('assignments', fn($sq) => $sq->where('department_id', $departmentId));
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereHas('attendances', fn ($aq) => $aq->whereBetween('date', [$startDate, $endDate]))
                  ->orWhere(function ($sq) use ($startDate) {
                      $sq->where('is_active', true)
                         ->whereDoesntHave('terminations', fn ($tq) => $tq->whereIn('status', ['approved', 'completed'])->where('termination_date', '<', $startDate));
                  })
                  ->orWhereHas('terminations', fn ($tq) => $tq->whereIn('status', ['approved', 'completed'])->where('termination_date', '>=', $startDate));
            })->get();

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])->get();

        $data = $employees->map(function ($emp) use ($attendances, $startDate, $endDate) {
            if ($emp->isExecutive()) {
                $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
                return [
                    'employee_id' => $emp->id,
                    'employee_code' => $emp->nip ?? $emp->employee_id ?? '-',
                    'name' => $emp->name,
                    'department_name' => $emp->assignments->pluck('department.name')->filter()->join(', ') ?: '-',
                    'total_present' => (int)$days,
                    'total_late' => 0,
                    'total_absent' => 0,
                    'total_leave' => 0,
                ];
            }
            $empAtt = $attendances->where('employee_id', $emp->id);
            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->nip ?? $emp->employee_id ?? '-',
                'name' => $emp->name,
                'department_name' => $emp->assignments->pluck('department.name')->filter()->join(', ') ?: '-',
                'total_present' => $empAtt->whereIn('status', ['present', 'hadir'])->count(),
                'total_late' => $empAtt->where('late_minutes', '>', 0)->count(),
                'total_absent' => $empAtt->whereIn('status', ['absent'])->count(),
                'total_leave' => $empAtt->whereIn('status', ['leave', 'sick', 'permit', 'permission'])->count(),
            ];
        });

        return response()->json(['data' => $data->values()]);
    }

    // 4. Laporan Keterlambatan
    public function tardiness(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->toDateString();
        $departmentId = $request->department_id;

        $query = Attendance::with(['employee.primaryAssignment.department', 'employee.primaryAssignment.position'])
            ->whereBetween('date', [$startDate, $endDate])
            ->where('late_minutes', '>', 0);

        if ($departmentId) {
            $query->whereHas('employee.primaryAssignment', fn($q) => $q->where('department_id', $departmentId));
        }

        $data = $query->orderBy('date', 'desc')->get();
        return response()->json(['data' => $data]);
    }

    // 5. Laporan Lembur (Overtime)
    public function overtime(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->toDateString();
        $status = $request->status;

        $query = OvertimeRequest::with(['employee.primaryAssignment.department', 'employee.primaryAssignment.position'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        $data = $query->orderBy('date', 'desc')->get();
        return response()->json(['data' => $data]);
    }

    // 6. Laporan Cuti
    public function leaves(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->toDateString();

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->whereBetween('start_date', [$startDate, $endDate]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query->get();
        return response()->json(['data' => $data]);
    }

    // 7. Laporan Penggajian
    public function payroll(Request $request)
    {
        $query = PayrollRun::with(['employee', 'assignment.department'])
            ->where('payroll_period_id', $request->payroll_period_id);

        if ($request->filled('department_id')) {
            $query->whereHas('assignment', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $data = $query->get();
        return response()->json(['data' => $data]);
    }

    // 8. Laporan Kasbon
    public function loans(Request $request)
    {
        $query = EmployeeLoan::with(['employee.primaryAssignment.department']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query->get();
        return response()->json(['data' => $data]);
    }

    // 9. Laporan Evaluasi KPI
    public function kpiSummary(Request $request)
    {
        $periodId = $request->period_id;

        $query = KpiAssessment::with(['employee.primaryAssignment.department', 'employee.primaryAssignment.position', 'period'])
            ->when($periodId, fn($q) => $q->where('period_id', $periodId))
            ->whereNotNull('final_score');

        $data = $query->orderByDesc('final_score')->get();
        return response()->json(['data' => $data]);
    }

    // 10. Laporan Pelanggaran (SP) & Mutasi
    public function violations(Request $request)
    {
        $warnings = EmployeeWarning::with(['employee.primaryAssignment.department'])->get();
        $mutations = EmployeeMutation::with(['employee.primaryAssignment.department'])->get();

        return response()->json([
            'data' => [
                'warnings' => $warnings,
                'mutations' => $mutations,
            ]
        ]);
    }

    // Export PDF Generic Endpoint
    public function exportPdf(Request $request)
    {
        $tokenStr = $request->query('token');
        if (!$tokenStr) {
            return abort(401, 'Unauthorized: Token is missing');
        }
        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenStr);
        if (!$token || !$token->tokenable) {
            return abort(401, 'Unauthorized: Invalid token');
        }

        $type = $request->type;
        $company = Company::first();

        if ($type === 'employee') {
            $query = Employee::with(['assignments.department', 'assignments.position']);
            if ($request->filled('status')) $query->whereHas('assignments', fn($sq) => $sq->where('employment_status', $request->status));
            if ($request->filled('department_id')) $query->whereHas('assignments', fn($sq) => $sq->where('department_id', $request->department_id));
            
            $data = $query->get();
            $pdf = Pdf::loadView('pdf.reports.employee', compact('data', 'company', 'request'))->setPaper('A4', 'landscape');
            return $pdf->stream('Laporan_Karyawan.pdf');
        } 
        
        else if ($type === 'individual') {
            $empRes = $this->individual($request);
            $data = $empRes->getData()->data ?? null;
            $pdf = Pdf::loadView('pdf.reports.individual', compact('data', 'company', 'request'))->setPaper('A4', 'portrait');
            $empName = str_replace(' ', '_', $data->employee->name ?? 'Employee');
            return $pdf->stream("Laporan_Individual_{$empName}.pdf");
        }

        else if ($type === 'attendance') {
            $startDate = $request->start_date ?? Carbon::today()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? Carbon::today()->toDateString();
            $employees = Employee::with('assignments.department')
                ->when($request->department_id, function ($q) use ($request) {
                    return $q->whereHas('assignments', fn($sq) => $sq->where('department_id', $request->department_id));
                })->get();
            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])->get();
            $data = $employees->map(function ($emp) use ($attendances, $startDate, $endDate) {
                if ($emp->isExecutive()) {
                    $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
                    return (object)[
                        'employee_code' => $emp->nip ?? $emp->employee_id ?? '-',
                        'name' => $emp->name,
                        'department_name' => $emp->assignments->pluck('department.name')->filter()->join(', ') ?: '-',
                        'total_present' => (int)$days,
                        'total_late' => 0,
                        'total_absent' => 0,
                        'total_leave' => 0,
                    ];
                }
                $empAtt = $attendances->where('employee_id', $emp->id);
                return (object)[
                    'employee_code' => $emp->nip ?? $emp->employee_id ?? '-',
                    'name' => $emp->name,
                    'department_name' => $emp->assignments->pluck('department.name')->filter()->join(', ') ?: '-',
                    'total_present' => $empAtt->whereIn('status', ['present', 'hadir'])->count(),
                    'total_late' => $empAtt->where('late_minutes', '>', 0)->count(),
                    'total_absent' => $empAtt->whereIn('status', ['absent'])->count(),
                    'total_leave' => $empAtt->whereIn('status', ['leave', 'sick', 'permit', 'permission'])->count(),
                ];
            });
            $pdf = Pdf::loadView('pdf.reports.attendance', compact('data', 'company', 'request'))->setPaper('A4', 'portrait');
            return $pdf->stream('Laporan_Kehadiran.pdf');
        }

        else if ($type === 'tardiness') {
            $tardinessRes = $this->tardiness($request);
            $data = $tardinessRes->getData()->data ?? [];
            $pdf = Pdf::loadView('pdf.reports.tardiness', compact('data', 'company', 'request'))->setPaper('A4', 'portrait');
            return $pdf->stream('Laporan_Keterlambatan.pdf');
        }

        else if ($type === 'overtime') {
            $overtimeRes = $this->overtime($request);
            $data = $overtimeRes->getData()->data ?? [];
            $pdf = Pdf::loadView('pdf.reports.overtime', compact('data', 'company', 'request'))->setPaper('A4', 'landscape');
            return $pdf->stream('Laporan_Lembur.pdf');
        }

        else if ($type === 'payroll') {
            $query = PayrollRun::with(['employee', 'assignment.department'])->where('payroll_period_id', $request->payroll_period_id);
            if ($request->filled('department_id')) {
                $query->whereHas('assignment', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }
            $data = $query->get();
            $period = \App\Models\PayrollPeriod::find($request->payroll_period_id);
            $pdf = Pdf::loadView('pdf.reports.payroll', compact('data', 'company', 'request', 'period'))->setPaper('A4', 'landscape');
            return $pdf->stream('Laporan_Penggajian.pdf');
        }

        else if ($type === 'leave') {
            $startDate = $request->start_date ?? Carbon::today()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? Carbon::today()->toDateString();
            $query = LeaveRequest::with(['employee', 'leaveType'])
                ->whereBetween('start_date', [$startDate, $endDate]);
            if ($request->filled('status')) $query->where('status', $request->status);
            
            $data = $query->get();
            $pdf = Pdf::loadView('pdf.reports.leave', compact('data', 'company', 'request'))->setPaper('A4', 'portrait');
            return $pdf->stream('Laporan_Cuti.pdf');
        }

        else if ($type === 'loan') {
            $query = EmployeeLoan::with(['employee.primaryAssignment.department']);
            if ($request->filled('status')) $query->where('status', $request->status);
            
            $data = $query->get();
            $pdf = Pdf::loadView('pdf.reports.loan', compact('data', 'company', 'request'))->setPaper('A4', 'portrait');
            return $pdf->stream('Laporan_Kasbon.pdf');
        }

        else if ($type === 'kpi_summary') {
            $kpiRes = $this->kpiSummary($request);
            $data = $kpiRes->getData()->data ?? [];
            $pdf = Pdf::loadView('pdf.reports.kpi_summary', compact('data', 'company', 'request'))->setPaper('A4', 'landscape');
            return $pdf->stream('Laporan_Evaluasi_KPI.pdf');
        }

        else if ($type === 'violations') {
            $violRes = $this->violations($request);
            $data = $violRes->getData()->data ?? null;
            $pdf = Pdf::loadView('pdf.reports.violations', compact('data', 'company', 'request'))->setPaper('A4', 'landscape');
            return $pdf->stream('Laporan_Pelanggaran_Mutasi.pdf');
        }

        return abort(404, 'Tipe laporan tidak ditemukan');
    }
}

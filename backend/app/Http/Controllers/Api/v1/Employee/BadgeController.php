<?php

namespace App\Http\Controllers\Api\v1\Employee;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\Task;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BadgeController extends Controller
{
    public function getBadges(Request $request)
    {
        $employee = Auth::user();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $employeeId = $employee->id;

        // 1. Pengajuan (Active leave/etc requests by this employee)
        $pengajuanCount = ApprovalRequest::where('requested_by', $employeeId)
            ->whereIn('module', ['leave', 'sick_leave', 'permission', 'workday_swap', 'asset_request', 'loan_request', 'overtime', 'reimbursement'])
            ->whereIn('status', ['pending', 'in_review'])
            ->count();
            
        $sakitCount = ApprovalRequest::where('requested_by', $employeeId)->where('module', 'sick_leave')->whereIn('status', ['pending', 'in_review'])->count();
        $ijinCount = ApprovalRequest::where('requested_by', $employeeId)->where('module', 'permission')->whereIn('status', ['pending', 'in_review'])->count();
        $cutiCount = ApprovalRequest::where('requested_by', $employeeId)->where('module', 'leave')->whereIn('status', ['pending', 'in_review'])->count();
        $tukarHariCount = ApprovalRequest::where('requested_by', $employeeId)->where('module', 'workday_swap')->whereIn('status', ['pending', 'in_review'])->count();

        // 2. Approval (Requests waiting for this employee to approve)
        $approvalCount = ApprovalRequest::whereIn('status', ['pending', 'in_review'])
            ->where(function ($q) use ($employeeId) {
                // Condition A: Current step is a 'specific_user' matching this employee
                $q->whereExists(function ($query) use ($employeeId) {
                    $query->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('approval_steps')
                          ->whereColumn('approval_steps.workflow_id', 'approval_requests.workflow_id')
                          ->whereColumn('approval_steps.order', 'approval_requests.current_step')
                          ->where('approval_steps.approver_type', 'specific_user')
                          ->where('approval_steps.approver_id', $employeeId);
                })
                // Condition B: Current step is 'direct_manager', 'supervisor', or 'manager' and this employee is the requester's supervisor
                ->orWhere(function ($q2) use ($employeeId) {
                    $q2->whereExists(function ($query) {
                        $query->select(\Illuminate\Support\Facades\DB::raw(1))
                              ->from('approval_steps')
                              ->whereColumn('approval_steps.workflow_id', 'approval_requests.workflow_id')
                              ->whereColumn('approval_steps.order', 'approval_requests.current_step')
                              ->whereIn('approval_steps.approver_type', ['direct_manager', 'supervisor', 'manager']);
                    })->where(function ($sub) use ($employeeId) {
                        $sub->whereHas('requester.primaryAssignment', function ($assignQuery) use ($employeeId) {
                            $assignQuery->where('supervisor_id', $employeeId);
                        })->orWhereHas('requester.assignments', function ($assignQuery) use ($employeeId) {
                            $assignQuery->where('supervisor_id', $employeeId);
                        });
                    });
                });
            })
            ->count();

        // 3. Tugas (Tasks assigned to this employee not completed)
        $taskCount = Task::whereHas('assignees', function($q) use ($employeeId) {
                $q->where('employees.id', $employeeId);
            })
            ->whereIn('status', ['todo', 'in_progress', 'review'])
            ->count();

        // 4. Notifikasi (Unread notifications)
        $notifCount = \App\Models\Notification::where('employee_id', $employeeId)
            ->where('is_read', false)
            ->count();

        // 5. Informasi (Unread announcements from the last 7 days)
        $infoCount = Announcement::where('created_at', '>=', now()->subDays(7))
            ->whereDoesntHave('reads', function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId);
            })->count();

        // 6. Overtime (Active overtime requests by this employee)
        $overtimeCount = ApprovalRequest::where('requested_by', $employeeId)
            ->where('module', 'overtime')
            ->whereIn('status', ['pending', 'in_review'])
            ->count();

        // 7. Kasbon
        $kasbonCount = ApprovalRequest::where('requested_by', $employeeId)
            ->where('module', 'loan_request')
            ->whereIn('status', ['pending', 'in_review'])
            ->count();

        // 8. Klaim/Reimbursement
        $reimburseCount = ApprovalRequest::where('requested_by', $employeeId)
            ->where('module', 'reimbursement')
            ->whereIn('status', ['pending', 'in_review'])
            ->count();

        // 9. Permit User
        $permitCount = ApprovalRequest::where('requested_by', $employeeId)
            ->where('module', 'permission')
            ->whereIn('status', ['pending', 'in_review'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'pengajuan_count' => $pengajuanCount,
                'approval_count' => $approvalCount,
                'task_count' => $taskCount,
                'notification_count' => $notifCount,
                'info_count' => $infoCount,
                'overtime_count' => $overtimeCount,
                'kasbon_count' => $kasbonCount,
                'reimburse_count' => $reimburseCount,
                'permit_count' => $permitCount,
                'pengajuan_sakit_count' => $sakitCount,
                'pengajuan_ijin_count' => $ijinCount,
                'pengajuan_cuti_count' => $cutiCount,
                'pengajuan_tukar_hari_count' => $tukarHariCount
            ]
        ]);
    }
}

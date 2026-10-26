<?php

namespace App\Services;

use App\Models\ApprovalLog;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\EmployeeApprovalLine;
use Illuminate\Support\Facades\DB;

/**
 * Approval Engine Service — engine umum untuk semua modul.
 *
 * Alur:
 * 1. dispatch(module, moduleId, requestedBy, payload)   → buat ApprovalRequest (status pending) untuk workflow aktif modul tsb.
 * 2. approve(request, admin, note) / reject(...)        → transisi status + tulis ApprovalLog + sinkronkan status modul.
 */
class ApprovalEngineService
{
    /**
     * Ambil workflow aktif untuk modul tertentu. Jika tidak ada, buat workflow
     * satu langkah otomatis (fallback sementara) agar proses tidak terblokir.
     */
    public function resolveWorkflow(string $module, ?int $requestedBy = null, ?int $moduleId = null): ApprovalWorkflow
    {
        if ($requestedBy) {
            // Cek employee_approval_lines terlebih dahulu
            $empLines = \App\Models\EmployeeApprovalLine::where('employee_id', $requestedBy)
                ->where('module', $module)
                ->orderBy('step_order')
                ->get();

            if ($empLines->isNotEmpty()) {
                $workflowName = "Dynamic: {$module} (Employee {$requestedBy})";
                $workflow = ApprovalWorkflow::firstOrCreate(
                    ['name' => $workflowName, 'module' => $module],
                    ['description' => 'Dynamic workflow based on EmployeeApprovalLine', 'is_active' => true]
                );

                $workflow->steps()->delete();
                foreach ($empLines as $line) {
                    $workflow->steps()->create([
                        'approver_type' => 'specific_user',
                        'approver_id' => $line->approver_id,
                        'order' => $line->step_order,
                        'is_optional' => $line->is_optional,
                    ]);
                }
                return $workflow->load('steps');
            }

            // Fallback ke CompanyApprovalLine
            $employee = \App\Models\Employee::with(['assignments', 'primaryAssignment'])->find($requestedBy);
            $companyId = $employee?->primaryAssignment?->company_id ?? $employee?->assignments->first()?->company_id;

            if ($companyId) {
                $lines = \App\Models\CompanyApprovalLine::where('company_id', $companyId)
                    ->where('module', $module)
                    ->orderBy('level')
                    ->get();

                if ($lines->isNotEmpty()) {
                    $workflowName = "Dynamic: {$module} (Company {$companyId})";
                    $workflow = ApprovalWorkflow::firstOrCreate(
                        ['name' => $workflowName, 'module' => $module],
                        ['description' => 'Dynamic workflow based on CompanyApprovalLine', 'is_active' => true]
                    );

                    $workflow->steps()->delete();
                    foreach ($lines as $line) {
                        $workflow->steps()->create([
                            'approver_type' => $line->approver_type,
                            'approver_id' => $line->approver_type === 'specific_user' ? $line->specific_user_id : null,
                            'order' => $line->level,
                            'is_optional' => false,
                        ]);
                    }
                    return $workflow->load('steps');
                }
            }

            // Khusus modul asset_request: integrasikan dengan AssetApprovalSetting jika ada
            if ($module === 'asset_request') {
                $employee = \App\Models\Employee::with('primaryAssignment')->find($requestedBy);
                $companyId = $employee?->primaryAssignment?->company_id;
                $branchId = $employee?->primaryAssignment?->branch_id;
                $deptId = $employee?->primaryAssignment?->department_id;

                $assetReq = $moduleId ? \App\Models\AssetRequest::find($moduleId) : null;
                $requestType = $assetReq?->request_type;
                $category = ($requestType === 'pengadaan_baru' || $requestType === 'pergantian_alat') 
                    ? 'permohonan_alat' 
                    : 'pengajuan_perlengkapan';

                $assetSetting = \App\Models\AssetApprovalSetting::where('is_active', true)
                    ->where(function ($q) use ($category) {
                        $q->where('approval_category', $category)
                          ->orWhereNull('approval_category');
                    })
                    ->where(function ($q) use ($companyId) {
                        $q->whereNull('company_id')->orWhere('company_id', $companyId);
                    })
                    ->where(function ($q) use ($branchId) {
                        $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
                    })
                    ->where(function ($q) use ($deptId) {
                        $q->whereNull('department_id')->orWhere('department_id', $deptId);
                    })
                    ->where(function ($q) use ($requestType) {
                        if ($requestType) {
                            $q->whereNull('request_type')->orWhere('request_type', $requestType);
                        }
                    })
                    ->orderByRaw('department_id IS NOT NULL DESC, branch_id IS NOT NULL DESC, company_id IS NOT NULL DESC, approval_category IS NOT NULL DESC')
                    ->with('stages')
                    ->first();

                if ($assetSetting && $assetSetting->stages->isNotEmpty()) {
                    $workflowName = "Asset: " . $assetSetting->name . ($companyId ? " (Company {$companyId})" : "");
                    $workflow = ApprovalWorkflow::firstOrCreate(
                        ['name' => $workflowName, 'module' => $module],
                        ['description' => 'Dynamic workflow based on AssetApprovalSetting: ' . $assetSetting->name, 'is_active' => true]
                    );

                    $workflow->steps()->delete();
                    foreach ($assetSetting->stages->sortBy('level') as $stage) {
                        $workflow->steps()->create([
                            'approver_type' => $stage->employee_id ? 'specific_user' : 'supervisor',
                            'approver_id' => $stage->employee_id,
                            'order' => $stage->level,
                            'is_optional' => false,
                        ]);
                    }
                    return $workflow->load('steps');
                }
            }
        }

        $workflow = ApprovalWorkflow::where('module', $module)
            ->where('is_active', true)
            ->where('name', 'not like', 'Dynamic:%')
            ->with('steps')
            ->first();

        if ($workflow && $workflow->steps->isNotEmpty()) {
            return $workflow;
        }

        // Fallback: workflow satu langkah "Supervisor" (tanpa approver spesifik).
        $workflow = ApprovalWorkflow::create([
            'name' => ucwords(str_replace('_', ' ', $module)) . ' (Default)',
            'module' => $module,
            'description' => 'Workflow default otomatis dibuat oleh Approval Engine.',
            'is_active' => true,
        ]);

        $workflow->steps()->create([
            'approver_type' => 'supervisor',
            'approver_id' => null,
            'order' => 1,
            'is_optional' => true,
        ]);

        return $workflow->load('steps');
    }

    /**
     * Buat approval request untuk modul.
     */
    public function dispatch(string $module, int $moduleId, int $requestedBy, ?array $payload = []): ApprovalRequest
    {
        $workflow = null;
        if (!empty($payload['custom_workflow_id'])) {
            $workflow = ApprovalWorkflow::with('steps')->find($payload['custom_workflow_id']);
        }
        if (!$workflow) {
            $workflow = $this->resolveWorkflow($module, $requestedBy, $moduleId);
        }

        $approval = ApprovalRequest::create([
            'workflow_id' => $workflow->id,
            'requested_by' => $requestedBy,
            'module' => $module,
            'module_id' => $moduleId,
            'payload' => $payload,
            'current_step' => 1,
            'status' => 'pending',
        ]);

        // Log submit
        $this->log($approval, null, null, $requestedBy, 'Pemohon', 'submit', 'Pengajuan dibuat.');

        // Notify next approver
        $this->notifyNextApprover($approval);

        return $approval;
    }

    /**
     * Approve approval request. Jika ini langkah terakhir → approved,
     * jika masih ada langkah berikutnya → in_review.
     */
    public function approve(ApprovalRequest $approval, ?int $adminId = null, ?int $employeeId = null, ?string $adminName = null, ?string $note = null): ApprovalRequest
    {
        $totalSteps = ApprovalStep::where('workflow_id', $approval->workflow_id)->count();
        $currentStepOrder = $approval->current_step;
        
        $currentStepRecord = ApprovalStep::where('workflow_id', $approval->workflow_id)
            ->where('order', $currentStepOrder)
            ->first();

        $this->log($approval, $currentStepRecord?->id, $adminId, $employeeId, $adminName ?? 'Admin', 'approve', $note ?? 'Disetujui.');

        if ($approval->module === 'asset_request') {
            \App\Models\AssetRequestApproval::where('asset_request_id', $approval->module_id)
                ->where('level', $currentStepOrder)
                ->update([
                    'status' => 'approved',
                    'approver_id' => $employeeId ?? \Illuminate\Support\Facades\DB::raw('approver_id'),
                    'action_date' => now(),
                    'notes' => $note,
                ]);

            if ($currentStepOrder === 1 && $employeeId) {
                \App\Models\AssetRequest::where('id', $approval->module_id)->update([
                    'acknowledged_by' => $employeeId,
                    'acknowledged_at' => now(),
                ]);
            }
        }

        // Resolusi Atasan Langsung dari pemohon untuk langkah berjenis supervisor
        $requester = \App\Models\Employee::with(['primaryAssignment', 'assignments'])->find($approval->requested_by);
        $directSupervisorId = $requester?->primaryAssignment?->supervisor_id 
            ?? $requester?->assignments?->first(fn($a) => !empty($a->supervisor_id))?->supervisor_id;

        // Kumpulkan semua employee_id yang sudah menyetujui pengajuan ini
        $approvedEmployeeIds = $approval->logs()
            ->where('action', 'approve')
            ->whereNotNull('employee_id')
            ->pluck('employee_id')
            ->toArray();
        if ($employeeId && !in_array($employeeId, $approvedEmployeeIds)) {
            $approvedEmployeeIds[] = $employeeId;
        }

        $nextStep = $currentStepOrder + 1;

        // Auto-approve langkah berikutnya jika approver-nya adalah orang yang sama dan sudah menyetujui pada tahap sebelumnya
        while ($nextStep <= $totalSteps) {
            $nextStepRecord = ApprovalStep::where('workflow_id', $approval->workflow_id)
                ->where('order', $nextStep)
                ->first();

            if (!$nextStepRecord) {
                break;
            }

            $nextApproverId = null;
            if ($nextStepRecord->approver_type === 'specific_user') {
                $nextApproverId = $nextStepRecord->approver_id;
            } elseif (in_array($nextStepRecord->approver_type, ['direct_manager', 'supervisor', 'manager'])) {
                $nextApproverId = $directSupervisorId;
            }

            if ($nextApproverId && in_array($nextApproverId, $approvedEmployeeIds)) {
                $autoNote = 'Disetujui otomatis (Telah disetujui pada tahap sebelumnya).';
                $this->log($approval, $nextStepRecord->id, $adminId, $nextApproverId, $adminName ?? 'System', 'approve', $autoNote);

                if ($approval->module === 'asset_request') {
                    \App\Models\AssetRequestApproval::where('asset_request_id', $approval->module_id)
                        ->where('level', $nextStep)
                        ->update([
                            'status' => 'approved',
                            'approver_id' => $nextApproverId,
                            'action_date' => now(),
                            'notes' => $autoNote,
                        ]);
                }

                $nextStep++;
            } else {
                break;
            }
        }

        if ($nextStep > $totalSteps) {
            $approval->update([
                'current_step' => $totalSteps,
                'status' => 'approved',
            ]);
            $this->syncModuleStatus($approval, 'approved');
            $this->notifyRequester($approval, 'Disetujui', 'Pengajuan ' . str_replace('_', ' ', $approval->module) . ' Anda telah disetujui.');
        } else {
            $approval->update([
                'current_step' => $nextStep,
                'status' => 'in_review',
            ]);
            
            $this->log($approval, null, null, null, 'System', 'escalate', 'Diteruskan ke langkah berikutnya.');
            $this->notifyNextApprover($approval);
        }

        return $approval->fresh();
    }

    /**
     * Reject approval request.
     */
    public function reject(ApprovalRequest $approval, ?int $adminId = null, ?int $employeeId = null, ?string $adminName = null, ?string $note = null): ApprovalRequest
    {
        $currentStepRecord = ApprovalStep::where('workflow_id', $approval->workflow_id)
            ->where('order', $approval->current_step)
            ->first();

        $this->log($approval, $currentStepRecord?->id, $adminId, $employeeId, $adminName ?? 'Admin', 'reject', $note ?? 'Ditolak.');

        if ($approval->module === 'asset_request') {
            \App\Models\AssetRequestApproval::where('asset_request_id', $approval->module_id)
                ->where('level', $approval->current_step)
                ->update([
                    'status' => 'rejected',
                    'approver_id' => $employeeId ?? \Illuminate\Support\Facades\DB::raw('approver_id'),
                    'action_date' => now(),
                    'notes' => $note,
                ]);
        }

        $approval->update(['status' => 'rejected']);
        $this->syncModuleStatus($approval, 'rejected');
        $this->notifyRequester($approval, 'Ditolak', 'Pengajuan ' . str_replace('_', ' ', $approval->module) . ' Anda telah ditolak. Catatan: ' . ($note ?? 'Ditolak.'));

        return $approval->fresh();
    }

    /**
     * Cancel approval request.
     */
    public function cancel(ApprovalRequest $approval, ?int $employeeId = null, ?string $employeeName = null, ?string $note = null): ApprovalRequest
    {
        $currentStepRecord = ApprovalStep::where('workflow_id', $approval->workflow_id)
            ->where('order', $approval->current_step)
            ->first();

        $this->log($approval, $currentStepRecord?->id, null, $employeeId, $employeeName ?? 'Pemohon', 'cancel', $note ?? 'Dibatalkan oleh pemohon.');

        $approval->update(['status' => 'cancelled']);
        $this->syncModuleStatus($approval, 'cancelled');

        return $approval->fresh();
    }

    protected function notifyRequester(ApprovalRequest $approval, string $status, string $message)
    {
        $employee = \App\Models\Employee::find($approval->requested_by);
        if ($employee && $employee->fcm_token) {
            $fcm = new \App\Services\FCMService();
            $fcm->sendToDevice($employee->fcm_token, "Pengajuan $status", $message, [
                'type' => 'approval_update',
                'module' => $approval->module,
                'module_id' => $approval->module_id
            ]);
        }
    }

    /**
     * Tulis log approval.
     */
    protected function log(ApprovalRequest $approval, ?int $stepId, ?int $adminId, ?int $employeeId, ?string $approverName, string $action, ?string $note): ApprovalLog
    {
        return ApprovalLog::create([
            'approval_request_id' => $approval->id,
            'approval_step_id' => $stepId,
            'admin_id' => $adminId,
            'employee_id' => $employeeId,
            'approver_name' => $approverName,
            'action' => $action,
            'note' => $note,
        ]);
    }

    /**
     * Sinkronkan status modul terkait berdasarkan modul & module_id.
     * Bisa diperluas per modul tanpa hardcode di controller.
     */
    protected function syncModuleStatus(ApprovalRequest $approval, string $status): void
    {
        $module = $approval->module;
        $moduleId = $approval->module_id;

        $modelMap = [
            'leave'       => \App\Models\LeaveRequest::class,
            'permission'  => \App\Models\PermissionRequest::class,
            'sick_leave'  => \App\Models\SickLeaveRequest::class,
            'late'        => \App\Models\LateRequest::class,
            'shift_swap'  => \App\Models\ShiftSwapRequest::class,
            'workday_swap'=> \App\Models\WorkdaySwapRequest::class,
            'loan_request'=> \App\Models\LoanRequest::class,
            'employee_mutations' => \App\Models\EmployeeMutation::class,
            'overtime' => \App\Models\OvertimeRequest::class,
            'reimbursement' => \App\Models\Reimbursement::class,
            'asset_request' => \App\Models\AssetRequest::class,
        ];

        $modelClass = $modelMap[$module] ?? null;
        if (!$modelClass) {
            return;
        }

        $record = $modelClass::find($moduleId);
        if (!$record) {
            return;
        }

        $data = ['status' => $status];
        if ($status === 'approved') {
            $data['approved_at'] = now();
        }
        if ($status === 'rejected') {
            $data['rejected_at'] = now();
        }

        $record->update($data);

        // Update Attendance or LeaveBalance depending on module
        DB::transaction(function () use ($module, $record, $status, $approval) {
            if ($module === 'leave' && $record instanceof \App\Models\LeaveRequest && $status === 'approved') {
                $this->adjustLeaveBalance($record, true);
                $start = $record->start_date ? \Carbon\Carbon::parse($record->start_date) : null;
                $end = $record->end_date ? \Carbon\Carbon::parse($record->end_date) : null;
                if ($start && $end) {
                    for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                        \App\Models\Attendance::updateOrCreate(
                            ['employee_id' => $record->employee_id, 'date' => $d->toDateString()],
                            ['status' => 'leave']
                        );
                    }
                }
            }

            if ($module === 'sick_leave' && $record instanceof \App\Models\SickLeaveRequest && $status === 'approved') {
                $start = $record->start_date ? \Carbon\Carbon::parse($record->start_date) : null;
                $end = $record->end_date ? \Carbon\Carbon::parse($record->end_date) : null;
                if ($start && $end) {
                    for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                        \App\Models\Attendance::updateOrCreate(
                            ['employee_id' => $record->employee_id, 'date' => $d->toDateString()],
                            ['status' => 'sick']
                        );
                    }
                }
            }

            if ($module === 'permission' && $record instanceof \App\Models\PermissionRequest && $status === 'approved') {
                if ($record->type === 'business') {
                    $emp = \App\Models\Employee::find($record->employee_id);
                    if ($emp) {
                        $emp->update(['can_clock_in_anywhere' => true]);
                    }
                }

                $pDate = $record->date ? \Carbon\Carbon::parse($record->date)->toDateString() : null;
                $eDate = $record->end_date ? \Carbon\Carbon::parse($record->end_date)->toDateString() : $pDate;

                if ($pDate) {
                    $attStatus = in_array($record->type, ['permit', 'early_leave', 'break', 'permit_early_leave', 'permit_break']) ? 'permit' : 'permission';
                    $updateData = ['status' => $attStatus];
                    
                    if ($record->start_time && $record->end_time) {
                        $updateData['break_start_at'] = $pDate . ' ' . $record->start_time;
                        $updateData['break_end_at'] = $pDate . ' ' . $record->end_time;
                    } elseif ($record->start_time) {
                        $updateData['clock_out_at'] = $pDate . ' ' . $record->start_time;
                    }

                    $start = \Carbon\Carbon::parse($pDate);
                    $end = \Carbon\Carbon::parse($eDate);
                    for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                        \App\Models\Attendance::updateOrCreate(
                            ['employee_id' => $record->employee_id, 'date' => $d->toDateString()],
                            $updateData
                        );
                    }
                }
            }

            if ($module === 'late' && $record instanceof \App\Models\LateRequest && $status === 'approved') {
                if ($record->attendance_id) {
                    \App\Models\Attendance::where('id', $record->attendance_id)->update(['status' => 'present']);
                }
            }

            if ($module === 'loan_request' && $record instanceof \App\Models\LoanRequest && $status === 'approved') {
                \App\Models\EmployeeLoan::create([
                    'employee_id' => $record->employee_id,
                    'loan_request_id' => $record->id,
                    'total_loan' => $record->amount,
                    'remaining_balance' => $record->amount,
                    'installment_amount' => $record->installment_months > 0 ? $record->amount / $record->installment_months : $record->amount,
                    'status' => 'active',
                ]);
            }

            if ($module === 'employee_mutations' && $record instanceof \App\Models\EmployeeMutation && $status === 'approved') {
                $employee = \App\Models\Employee::find($record->employee_id);
                if ($employee) {
                    $oldNip = $employee->employee_id;
                    $newNip = $record->new_nip ?: $oldNip;

                    // Assignment aktif saat ini (posisi asal)
                    $oldAssign = \App\Models\EmployeeAssignment::where('employee_id', $employee->id)
                        ->where('is_primary', 1)
                        ->first();

                    // Tanggal efektif mutasi
                    $effectiveDate = $record->effective_date ? \Carbon\Carbon::parse($record->effective_date) : now();
                    $endDate = $effectiveDate->copy()->subDay()->toDateString();

                    // 1. Arsipkan assignment lama sebagai riwayat penugasan
                    if ($oldAssign) {
                        $oldAssign->update([
                            'is_primary' => false,
                            'end_date' => $endDate,
                            'nip' => $oldAssign->nip ?: $oldNip,
                        ]);
                    }

                    // 2. Buat assignment baru untuk penempatan / entitas / jabatan baru
                    $newAssign = \App\Models\EmployeeAssignment::create([
                        'employee_id' => $employee->id,
                        'company_id' => $record->new_company_id,
                        'placement_company_id' => $record->new_company_id,
                        'branch_id' => $record->new_branch_id,
                        'department_id' => $record->new_department_id,
                        'division_id' => $record->new_division_id,
                        'position_id' => $record->new_position_id,
                        'supervisor_id' => $record->new_supervisor_id,
                        'join_date' => $effectiveDate->toDateString(),
                        'end_date' => null,
                        'employment_status' => $oldAssign ? $oldAssign->employment_status : 'permanent',
                        'salary_cycle_type' => $oldAssign ? $oldAssign->salary_cycle_type : 'MONTHLY_PAID_MONTHLY',
                        'is_primary' => true,
                        'nip' => $newNip,
                    ]);

                    // 3. Salin komponen gaji dari assignment lama ke assignment baru
                    if ($oldAssign) {
                        $oldComponents = \App\Models\EmployeeSalaryComponent::where('employee_id', $employee->id)
                            ->where('employee_assignment_id', $oldAssign->id)
                            ->get();
                        foreach ($oldComponents as $oldComp) {
                            \App\Models\EmployeeSalaryComponent::create([
                                'employee_id' => $employee->id,
                                'employee_assignment_id' => $newAssign->id,
                                'payroll_component_id' => $oldComp->payroll_component_id,
                                'amount' => $oldComp->amount,
                                'calculation_type' => $oldComp->calculation_type ?? 'fixed',
                                'formula' => $oldComp->formula,
                                'percentage' => $oldComp->percentage,
                                'effective_from' => $effectiveDate->toDateString(),
                                'is_active' => true,
                            ]);
                        }
                    }

                    // Jika ada PositionPayrollComponent untuk posisi baru dan belum ada di assignment baru
                    if ($record->new_position_id) {
                        $positionComps = \App\Models\PositionPayrollComponent::where('position_id', $record->new_position_id)->get();
                        foreach ($positionComps as $pc) {
                            \App\Models\EmployeeSalaryComponent::firstOrCreate(
                                [
                                    'employee_id' => $employee->id,
                                    'employee_assignment_id' => $newAssign->id,
                                    'payroll_component_id' => $pc->payroll_component_id,
                                ],
                                [
                                    'amount' => $pc->fixed_amount,
                                    'calculation_type' => 'fixed',
                                    'effective_from' => $effectiveDate->toDateString(),
                                    'is_active' => true,
                                ]
                            );
                        }
                    }

                    // 4. Update NIP aktif karyawan di tabel employees jika ada NIP baru
                    if ($record->new_nip) {
                        $employee->update(['employee_id' => $record->new_nip]);
                    }
                }
            }

            if ($module === 'asset_request' && $record instanceof \App\Models\AssetRequest) {
                if ($status === 'approved') {
                    $approverEmpId = $approval->logs()->where('action', 'approve')->latest()->first()?->employee_id;
                    $record->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                        'approved_by' => $approverEmpId ?: $record->approved_by,
                    ]);
                } elseif ($status === 'rejected') {
                    $latestLog = $approval->logs()->where('action', 'reject')->latest()->first();
                    $record->update([
                        'status' => 'rejected',
                        'admin_notes' => $latestLog?->note ? ('Ditolak: ' . $latestLog->note) : $record->admin_notes,
                    ]);
                }
            }
        });
    }

    /**
     * Kurangi / kembalikan kuota leave balance.
     */
    protected function adjustLeaveBalance(\App\Models\LeaveRequest $leave, bool $deduct): void
    {
        $year = $leave->start_date?->year ?? now()->year;
        $balance = \App\Models\LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            return;
        }

        $days = (float) $leave->total_days;

        if ($deduct) {
            $balance->update([
                'used' => round(((float) $balance->used) + $days, 1),
                'remaining' => round(((float) $balance->remaining) - $days, 1),
            ]);
        } else {
            $balance->update([
                'used' => round(max(0, ((float) $balance->used) - $days), 1),
                'remaining' => round(((float) $balance->remaining) + $days, 1),
            ]);
        }
    }

    protected function notifyNextApprover(ApprovalRequest $approval)
    {
        $nextStep = \App\Models\ApprovalStep::where('workflow_id', $approval->workflow_id)
            ->where('order', $approval->current_step)
            ->first();

        if (!$nextStep) return;

        $approverId = null;

        if ($nextStep->approver_type === 'specific_user') {
            $approverId = $nextStep->approver_id;
        } elseif (in_array($nextStep->approver_type, ['direct_manager', 'supervisor', 'manager'])) {
            $requester = \App\Models\Employee::with(['primaryAssignment', 'assignments'])->find($approval->requested_by);
            if ($requester) {
                $approverId = $requester->primaryAssignment?->supervisor_id
                    ?? $requester->assignments->first(fn($a) => !empty($a->supervisor_id))?->supervisor_id;
            }
        }

        if ($approverId) {
            $approver = \App\Models\Employee::find($approverId);
            if ($approver && $approver->fcm_token) {
                $requesterName = $approval->requester->name ?? 'Karyawan';
                $fcm = new \App\Services\FCMService();
                $fcm->sendToDevice($approver->fcm_token, "Persetujuan Diperlukan", "Ada pengajuan baru dari $requesterName yang menunggu persetujuan Anda.", [
                    'type' => 'approval_request',
                    'module' => $approval->module,
                    'module_id' => $approval->module_id
                ]);
            }
        }
    }
}

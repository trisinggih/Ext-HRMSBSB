<?php

namespace App\Http\Resources\Approval;

use App\Http\Resources\Master\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attachmentUrl = null;
        $modelMap = [
            'leave'       => \App\Models\LeaveRequest::class,
            'permission'  => \App\Models\PermissionRequest::class,
            'sick_leave'  => \App\Models\SickLeaveRequest::class,
            'late'        => \App\Models\LateRequest::class,
            'shift_swap'  => \App\Models\ShiftSwapRequest::class,
            'workday_swap'=> \App\Models\WorkdaySwapRequest::class,
            'loan_request'=> \App\Models\LoanRequest::class,
            'overtime'    => \App\Models\OvertimeRequest::class,
            'reimbursement' => \App\Models\Reimbursement::class,
            'employee_mutations' => \App\Models\EmployeeMutation::class,
            'asset_request' => \App\Models\AssetRequest::class,
        ];

        if (isset($modelMap[$this->module])) {
            $record = $modelMap[$this->module]::find($this->module_id);
            if ($record) {
                if (!empty($record->attachment)) {
                    $attachmentUrl = url('storage/' . $record->attachment);
                } elseif (!empty($record->photo)) {
                    $attachmentUrl = url('storage/' . $record->photo);
                } elseif (!empty($record->medical_certificate)) {
                    $attachmentUrl = url('storage/' . $record->medical_certificate);
                }
            }
        }

        $payload = $this->payload ?? [];
        if ($this->module === 'permission' && isset($record) && $record) {
            $startDateStr = $record->start_date ?: ($record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : substr((string)$record->date, 0, 10));
            $endDateStr = $record->end_date ?: $startDateStr;
            $payload = array_merge([
                'type' => $record->type,
                'date' => $startDateStr,
                'start_date' => $startDateStr,
                'end_date' => $endDateStr,
                'start_time' => $record->start_time,
                'end_time' => $record->end_time,
                'reason' => $record->reason,
            ], $payload);
        }

        if ($this->module === 'asset_request') {
            $assetRequest = \App\Models\AssetRequest::with(['items', 'assetType', 'branch', 'department'])->find($this->module_id);
            if ($assetRequest) {
                $payload = array_merge($payload, [
                    'item_name' => $assetRequest->item_name,
                    'quantity' => $assetRequest->quantity,
                    'estimated_price' => (float) $assetRequest->estimated_price,
                    'unit_price' => $assetRequest->quantity > 0 ? (float) ($assetRequest->estimated_price / $assetRequest->quantity) : 0,
                    'purpose' => $assetRequest->purpose,
                    'request_type' => $assetRequest->request_type,
                    'for_who' => $assetRequest->for_who,
                    'placement' => $assetRequest->placement,
                    'notes' => $assetRequest->notes ?: $assetRequest->specifications,
                    'branch_name' => $assetRequest->branch?->name,
                    'department_name' => $assetRequest->department?->name,
                    'asset_type_name' => $assetRequest->assetType?->name,
                    'items' => $assetRequest->items->map(function ($it) {
                        return [
                            'id' => $it->id,
                            'item_name' => $it->item_name,
                            'item_size' => $it->item_size,
                            'quantity' => $it->quantity,
                            'placement' => $it->placement,
                            'unit_price' => (float) ($it->unit_price ?: ($it->quantity > 0 ? $it->estimated_price / $it->quantity : 0)),
                            'estimated_price' => (float) $it->estimated_price,
                            'notes' => $it->notes,
                        ];
                    })->toArray(),
                ]);
            }
        }

        if ($this->module === 'employee_mutations') {
            $mutation = \App\Models\EmployeeMutation::with([
                'employee', 
                'oldCompany', 'oldBranch', 'oldDepartment', 'oldDivision', 'oldPosition',
                'newCompany', 'newBranch', 'newDepartment', 'newDivision', 'newPosition'
            ])->find($this->module_id);
            if ($mutation) {
                $payload = array_merge([
                    'memo_number' => $mutation->memo_number,
                    'mutation_type' => $mutation->type,
                    'type' => $mutation->type,
                    'effective_date' => $mutation->effective_date,
                    'reason' => $mutation->reason,
                    'employee_name' => $mutation->employee?->name,
                    'employee_nik' => $mutation->employee?->employee_id,
                    'old_company_name' => $mutation->oldCompany?->name,
                    'old_branch_name' => $mutation->oldBranch?->name,
                    'old_department_name' => $mutation->oldDepartment?->name,
                    'old_division_name' => $mutation->oldDivision?->name,
                    'old_position_name' => $mutation->oldPosition?->name,
                    'new_company_name' => $mutation->newCompany?->name,
                    'new_branch_name' => $mutation->newBranch?->name,
                    'new_department_name' => $mutation->newDepartment?->name,
                    'new_division_name' => $mutation->newDivision?->name,
                    'new_position_name' => $mutation->newPosition?->name,
                ], $payload);
            }
        }

        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'requested_by' => $this->requested_by,
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'employee_id' => $this->requester->employee_id,
                'photo' => $this->requester->photo,
            ]),
            'module' => $this->module,
            'module_id' => $this->module_id,
            'payload' => $payload,
            'attachment_url' => $attachmentUrl,
            'current_step' => $this->current_step,
            'status' => $this->status,
                        'workflow' => $this->whenLoaded('workflow', function () {
                return [
                    'id' => $this->workflow->id,
                    'name' => $this->workflow->name,
                    'steps' => $this->workflow->steps->map(function ($step) {
                        $expectedName = 'Unknown';
                        if ($step->approver_type === 'specific_user' && $step->approver_id) {
                            $expectedName = \App\Models\Employee::find($step->approver_id)?->name ?? 'User (ID: '.$step->approver_id.')';
                        } elseif (in_array($step->approver_type, ['direct_manager', 'supervisor', 'manager'])) {
                            $requester = \App\Models\Employee::with(['primaryAssignment.supervisor', 'assignments.supervisor'])->find($this->requested_by);
                            $spv = $requester?->primaryAssignment?->supervisor
                                ?? $requester?->assignments->first(fn($a) => $a->supervisor)?->supervisor;
                            if ($spv) {
                                $expectedName = $spv->name;
                            } else {
                                $expectedName = 'Atasan Langsung';
                            }
                        } elseif ($step->approver_type === 'hr') {
                            $expectedName = 'HR Department';
                        }
                        return [
                            'id' => $step->id,
                            'order' => $step->order,
                            'approver_type' => $step->approver_type,
                            'is_optional' => $step->is_optional,
                            'expected_approver_name' => $expectedName,
                        ];
                    }),
                ];
            }),
            'logs' => $this->whenLoaded('logs', fn () => ApprovalLogResource::collection($this->logs)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

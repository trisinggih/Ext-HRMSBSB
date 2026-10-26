<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isStaffHr = ($user instanceof \App\Models\Employee && $user->web_role === 'staff_hr');

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'address' => $this->address,
            'domicile_address' => $this->domicile_address,
            'residential_address' => $this->residential_address,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            'face_photo' => $this->face_photo ? asset('storage/' . $this->face_photo) : null,
            'ktp_number' => $this->ktp_number,
            'npwp' => $this->npwp,
            'marital_status' => $this->marital_status,
            'ptkp_status' => $this->ptkp_status,
            'bpjs_kesehatan' => $this->bpjs_kesehatan,
            'bpjs_ketenagakerjaan' => $this->bpjs_ketenagakerjaan,
            'is_active' => $this->is_active,
            'is_daily_worker' => (bool)$this->is_daily_worker,
            'can_clock_in_anywhere' => (bool)$this->can_clock_in_anywhere,
            'attendance_location_type' => $this->attendance_location_type ?? 'assigned_branch',
            'allowed_branch_ids' => $this->allowed_branch_ids ?? [],
            'web_access_enabled' => (bool)$this->web_access_enabled,
            'web_role' => $this->web_role,
            'managed_division_ids' => $this->managed_division_ids ?? [],
            'last_latitude' => $this->latestLocation?->latitude,
            'last_longitude' => $this->latestLocation?->longitude,
            'assignments' => $this->whenLoaded('assignments', function() use ($isStaffHr) {
                return $this->assignments->map(function ($assignment) use ($isStaffHr) {
                    return [
                        'id' => $assignment->id,
                        'company_id' => $assignment->company_id,
                        'placement_company_id' => $assignment->placement_company_id,
                        'branch_id' => $assignment->branch_id,
                        'department_id' => $assignment->department_id,
                        'division_id' => $assignment->division_id,
                        'position_id' => $assignment->position_id,
                        'supervisor_id' => $assignment->supervisor_id,
                        'join_date' => $assignment->join_date?->toDateString(),
                        'employment_status' => $assignment->employment_status,
                        'is_primary' => $assignment->is_primary,
                        'salary_cycle_type' => $assignment->salary_cycle_type,
                        'payroll_components' => (!$isStaffHr && $assignment->relationLoaded('payrollComponents')) ? $assignment->payrollComponents->map(function ($comp) use ($assignment) {
                            $salaryComp = \App\Models\EmployeeSalaryComponent::where('employee_assignment_id', $assignment->id)
                                ->where('payroll_component_id', $comp->id)
                                ->first();
                            return [
                                'payroll_component_id' => $comp->id,
                                'fixed_amount' => $salaryComp ? $salaryComp->amount : 0,
                            ];
                        }) : [],
                        'company' => $assignment->relationLoaded('company') && $assignment->company ? new CompanyResource($assignment->company) : null,
                        'placement_company' => $assignment->relationLoaded('placementCompany') && $assignment->placementCompany ? new CompanyResource($assignment->placementCompany) : null,
                        'branch' => $assignment->relationLoaded('branch') && $assignment->branch ? new BranchResource($assignment->branch) : null,
                        'department' => $assignment->relationLoaded('department') && $assignment->department ? new DepartmentResource($assignment->department) : null,
                        'division' => $assignment->relationLoaded('division') && $assignment->division ? new DivisionResource($assignment->division) : null,
                        'position' => $assignment->relationLoaded('position') && $assignment->position ? new PositionResource($assignment->position) : null,
                        'supervisor' => $assignment->relationLoaded('supervisor') && $assignment->getRelation('supervisor') ? new EmployeeResource($assignment->getRelation('supervisor')) : null,
                    ];
                });
            }),
            'families' => $this->whenLoaded('families'),
            'documents' => $this->whenLoaded('documents'),
            'faces' => $this->whenLoaded('faces'),
            'devices' => $this->whenLoaded('devices'),
            'shifts' => $this->whenLoaded('shifts', function() {
                return $this->shifts->loadMissing('shift');
            }),
            'payroll_settings' => $isStaffHr ? null : $this->whenLoaded('payrollSettings'),
            'salary_components' => $isStaffHr ? [] : $this->whenLoaded('salaryComponents', function() {
                return $this->salaryComponents->loadMissing('component');
            }),
            'locations' => $this->whenLoaded('locationTrackingSessions'),
            'warnings' => $this->whenLoaded('warnings'),
            'terminations' => $this->whenLoaded('terminations'),
            'latest_termination' => $this->whenLoaded('terminations', function () {
                $term = $this->terminations->sortByDesc('id')->first();
                return $term ? [
                    'id' => $term->id,
                    'type' => $term->type,
                    'termination_date' => $term->termination_date?->toDateString(),
                    'reason' => $term->reason,
                    'status' => $term->status,
                ] : null;
            }),
            'signature_url' => $this->signature_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}


<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'division_id' => $this->division_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'base_salary' => $this->base_salary,
            'is_active' => $this->is_active,
            'level' => $this->level,
            'is_department_manager' => \App\Models\Department::where('manager_position_id', $this->id)->exists(),
            'parent_id' => $this->parent_id,
            'parent_name' => $this->whenLoaded('parent', fn () => $this->parent->name),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'division' => new DivisionResource($this->whenLoaded('division')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'payroll_components' => $this->whenLoaded('payrollComponents', function() {
                return $this->payrollComponents->map(function($pc) {
                    return [
                        'id' => $pc->id,
                        'payroll_component_id' => $pc->payroll_component_id,
                        'fixed_amount' => (float) $pc->fixed_amount,
                        'component_name' => $pc->component?->name
                    ];
                });
            }),
        ];
    }
}


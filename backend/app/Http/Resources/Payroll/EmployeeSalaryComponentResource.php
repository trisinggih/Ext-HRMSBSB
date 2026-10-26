<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSalaryComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'payroll_component_id' => $this->payroll_component_id,
            'amount' => $this->amount,
            'percentage' => $this->percentage,
            'effective_from' => $this->effective_from?->toDateString(),
            'is_active' => $this->is_active,
            'component' => new PayrollComponentResource($this->whenLoaded('component')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

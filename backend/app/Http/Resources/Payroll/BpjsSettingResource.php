<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BpjsSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bpjs_type' => $this->bpjs_type,
            'name' => $this->name,
            'employee_rate' => $this->employee_rate,
            'company_rate' => $this->company_rate,
            'max_limit' => $this->max_limit,
            'effective_date' => $this->effective_date?->toDateString(),
            'is_active' => $this->is_active,
            'is_tax_deductible' => $this->is_tax_deductible,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

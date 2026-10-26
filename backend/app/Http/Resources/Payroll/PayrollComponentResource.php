<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'payment_schema' => $this->payment_schema,
            'calculation_type' => $this->calculation_type,
            'amount' => $this->amount,
            'percentage' => $this->percentage,
            'formula' => $this->formula,
            'has_rules' => $this->has_rules,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_taxable' => $this->is_taxable,
            'calculation_source' => $this->calculation_source,
            'calculation_method' => $this->calculation_method,
            'default_value' => $this->default_value,
            'is_bpjs_basis' => $this->is_bpjs_basis,
            'description' => $this->description,
            'show_on_payslip' => $this->show_on_payslip,
            'effective_from' => $this->effective_from?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

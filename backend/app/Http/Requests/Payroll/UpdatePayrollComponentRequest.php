<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|max:100|unique:payroll_components,code,' . $this->route('component')?->id,
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:earning,deduction,tax,bpjs',
            'category' => 'sometimes|string|in:monthly,annual,loan,weekly,daily',
            'payment_schema' => 'nullable|array',
            'payment_schema.*' => 'in:DAILY_PAID_MONTHLY,MONTHLY_PAID_MONTHLY,DAILY_PAID_WEEKLY,DAILY_PAID_DAILY',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'is_taxable' => 'sometimes|boolean',
            'calculation_source' => 'sometimes|string',
            'calculation_method' => 'sometimes|string',
            'default_value' => 'nullable|numeric',
            'is_bpjs_basis' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'show_on_payslip' => 'sometimes|boolean',
            'effective_from' => 'nullable|date',
            'has_rules' => 'sometimes|boolean',
            'formula' => 'nullable|string',
        ];
    }
}




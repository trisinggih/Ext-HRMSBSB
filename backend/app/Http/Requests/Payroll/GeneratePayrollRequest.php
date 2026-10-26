<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payroll_period_id' => 'required|integer|exists:payroll_periods,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:employees,id',
        ];
    }
}

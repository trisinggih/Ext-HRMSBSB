<?php

namespace App\Http\Requests\PayrollDeduction;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'payroll_period_id' => 'nullable|integer|exists:payroll_periods,id',
            'type' => 'sometimes|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'deduction_date' => 'required|date',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}

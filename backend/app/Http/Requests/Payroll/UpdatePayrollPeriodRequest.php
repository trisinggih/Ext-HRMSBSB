<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;


class UpdatePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['sometimes', 'exists:companies,id'],
            'branch_id' => ['sometimes', 'nullable', 'exists:branches,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'cycle_type' => ['sometimes', 'in:monthly,daily_paid_monthly,weekly,weekly_payment,weekly_payslip,daily'],
            'period' => ['sometimes', 'string', 'max:10'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'payment_date' => ['nullable', 'date'],
        ];
    }
}

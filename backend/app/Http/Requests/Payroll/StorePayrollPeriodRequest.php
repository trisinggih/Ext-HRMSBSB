<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'cycle_type' => ['required', 'in:monthly,daily_paid_monthly,weekly,weekly_payment,weekly_payslip,daily'],
            'period' => ['nullable', 'string', 'max:10'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'payment_date' => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Http\Requests\Reward;

use Illuminate\Foundation\Http\FormRequest;

class StoreRewardRequest extends FormRequest
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
            'type' => 'sometimes|in:employee_of_month,performance,bonus,appreciation,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'reward_date' => 'required|date',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}

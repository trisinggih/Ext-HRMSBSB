<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tax_rules,code',
            'period' => 'required|in:yearly,monthly',
            'effective_date' => 'required|date',
            'is_active' => 'sometimes|boolean',
            // optional: brackets disertakan saat buat rule sekaligus
            'brackets' => 'sometimes|array',
            'brackets.*.min_amount' => 'required|numeric|min:0',
            'brackets.*.max_amount' => 'nullable|numeric',
            'brackets.*.rate' => 'required|numeric|min:0|max:100',
        ];
    }
}

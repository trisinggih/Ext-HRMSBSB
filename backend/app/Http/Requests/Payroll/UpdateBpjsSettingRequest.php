<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBpjsSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bpjs_type' => 'sometimes|string|max:50',
            'name' => 'nullable|string|max:255',
            'employee_rate' => 'sometimes|numeric|min:0|max:100',
            'company_rate' => 'sometimes|numeric|min:0|max:100',
            'max_limit' => 'nullable|numeric|min:0',
            'effective_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
            'is_tax_deductible' => 'sometimes|boolean',
        ];
    }
}

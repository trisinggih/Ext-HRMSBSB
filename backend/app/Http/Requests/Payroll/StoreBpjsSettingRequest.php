<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StoreBpjsSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bpjs_type' => 'required|string|max:50',
            'name' => 'nullable|string|max:255',
            'employee_rate' => 'required|numeric|min:0|max:100',
            'company_rate' => 'required|numeric|min:0|max:100',
            'max_limit' => 'nullable|numeric|min:0',
            'effective_date' => 'required|date',
            'is_active' => 'sometimes|boolean',
            'is_tax_deductible' => 'sometimes|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'items' => 'required|array|min:1',
            'items.*.payroll_component_id' => 'required|integer|exists:payroll_components,id',
            'items.*.amount' => 'nullable|numeric|min:0',
            'items.*.percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.effective_from' => 'required|date',
            'items.*.is_active' => 'sometimes|boolean',
        ];
    }
}

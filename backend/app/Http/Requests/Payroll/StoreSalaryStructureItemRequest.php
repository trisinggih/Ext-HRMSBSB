<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payroll_component_id' => 'required|integer|exists:payroll_components,id',
            'amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'effective_from' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

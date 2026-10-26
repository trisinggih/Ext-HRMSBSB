<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'base_salary' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'level' => 'nullable|string|max:50',
            'parent_id' => 'nullable|integer|exists:positions,id',
            'payroll_components' => 'nullable|array',
            'payroll_components.*.payroll_component_id' => 'required|exists:payroll_components,id',
            'payroll_components.*.fixed_amount' => 'nullable|numeric',
        ];
    }
}


<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'nullable|integer|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'manager_id' => 'nullable|integer|exists:employees,id',
            'parent_id' => 'nullable|integer|exists:departments,id',
            'manager_position_id' => 'nullable|integer|exists:positions,id',
        ];
    }
}

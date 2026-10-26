<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => 'nullable|integer|exists:departments,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}


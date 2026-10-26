<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => 'nullable|integer|exists:departments,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}


<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'role' => 'sometimes|in:assignee,reviewer',
        ];
    }
}

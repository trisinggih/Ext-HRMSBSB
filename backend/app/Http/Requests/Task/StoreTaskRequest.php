<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|integer|exists:employees,id',
            'role' => 'sometimes|in:assignee,reviewer',
            'due_date' => 'nullable|date',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:todo,in_progress,review,done,cancelled',
            'progress' => 'sometimes|integer|min:0|max:100',
            'attachment' => 'nullable|file|max:5120',
        ];
    }
}

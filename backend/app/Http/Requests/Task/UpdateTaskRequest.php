<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:todo,in_progress,review,done,cancelled',
            'progress' => 'sometimes|integer|min:0|max:100',
            'attachment' => 'nullable|file|max:5120',
        ];
    }
}

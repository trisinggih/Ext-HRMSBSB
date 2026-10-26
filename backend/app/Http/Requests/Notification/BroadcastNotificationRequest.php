<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'data' => ['nullable', 'array'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ];
    }
}

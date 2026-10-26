<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'data' => ['nullable', 'array'],
        ];
    }
}

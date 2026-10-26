<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => 'required|in:online,offline',
            'scheduled_at' => 'required|date',
            'location' => 'nullable|string',
            'meeting_link' => 'nullable|string',
            'interviewer_id' => 'nullable|integer|exists:admins,id',
            'notes' => 'nullable|string',
        ];
    }
}

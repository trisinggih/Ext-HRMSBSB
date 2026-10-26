<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class HistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'status' => 'nullable|string|in:present,late,absent,leave,sick,permission,holiday',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}

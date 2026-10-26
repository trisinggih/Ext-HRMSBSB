<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class InterviewResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decided' => 'required|in:hire,reject,hold',
            'result_notes' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => 'nullable|in:scheduled,confirmed,completed,cancelled',
        ];
    }
}

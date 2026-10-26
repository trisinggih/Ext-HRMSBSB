<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => 'sometimes|string',
            'option_a' => 'sometimes|string',
            'option_b' => 'sometimes|string',
            'option_c' => 'sometimes|string',
            'option_d' => 'sometimes|string',
            'correct_answer' => 'sometimes|in:A,B,C,D',
            'score' => 'nullable|integer|min:1',
        ];
    }
}

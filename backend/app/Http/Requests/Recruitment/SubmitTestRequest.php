<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_id' => 'required_without:email|integer|exists:candidates,id',
            'email' => 'required_without:candidate_id|email|exists:candidates,email',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:test_questions,id',
            'answers.*.chosen' => 'sometimes|in:A,B,C,D',
        ];
    }
}

<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:applied,document_review,test_invited,test_done,interview_invited,interview_done,shortlisted,offered,hired,rejected,on_hold',
            'notes' => 'nullable|string',
        ];
    }
}

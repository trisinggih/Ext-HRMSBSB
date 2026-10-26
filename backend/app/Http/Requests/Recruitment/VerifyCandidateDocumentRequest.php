<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCandidateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_verified' => 'required|boolean',
            'notes' => 'nullable|string|max:255',
        ];
    }
}

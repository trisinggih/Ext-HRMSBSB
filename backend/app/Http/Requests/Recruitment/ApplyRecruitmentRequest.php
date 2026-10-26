<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class ApplyRecruitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'ktp_number' => 'required|string|max:30',
            'source' => 'nullable|string|max:100',
            'job_id' => 'required|integer|exists:recruitment_jobs,id',
            'documents' => 'nullable|array',
            'documents.*.document_type' => 'required_with:documents|in:cv,kk,ktp,foto,skck,ijazah,lainnya',
            'documents.*.file' => 'required_with:documents|file|mimes:pdf,jpeg,jpg,png,doc,docx|max:4096',
        ];
    }
}

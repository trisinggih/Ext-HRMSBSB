<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recruitment_job_id' => 'nullable|integer|exists:recruitment_jobs,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecruitmentJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:recruitment_jobs,code',
            'title' => 'required|string|max:255',
            'company_id' => 'nullable|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'position_id' => 'nullable|integer|exists:positions,id',
            'vacancy_quota' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|gte:salary_min',
            'status' => 'nullable|in:draft,open,closed',
            'application_deadline' => 'nullable|date',
        ];
    }
}

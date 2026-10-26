<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecruitmentJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $job = $this->route('job');

        return [
            'code' => 'sometimes|string|max:50|unique:recruitment_jobs,code,' . $job->id,
            'title' => 'sometimes|string|max:255',
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

<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'vacancy_quota' => $this->vacancy_quota,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'salary_min' => (float) $this->salary_min,
            'salary_max' => (float) $this->salary_max,
            'status' => $this->status,
            'application_deadline' => $this->application_deadline?->toDateString(),
            'created_by' => $this->created_by,
            'company' => $this->whenLoaded('company'),
            'branch' => $this->whenLoaded('branch'),
            'department' => $this->whenLoaded('department'),
            'position' => $this->whenLoaded('position'),
            'tests' => TestCollection::make($this->whenLoaded('tests')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

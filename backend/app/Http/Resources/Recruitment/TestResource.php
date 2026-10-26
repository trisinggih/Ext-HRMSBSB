<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recruitment_job_id' => $this->recruitment_job_id,
            'name' => $this->name,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'passing_score' => $this->passing_score,
            'status' => (bool) $this->status,
            'recruitment_job' => $this->whenLoaded('recruitmentJob'),
            'questions' => TestQuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

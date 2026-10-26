<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'recruitment_job_id' => $this->recruitment_job_id,
            'status' => $this->status,
            'applied_at' => $this->applied_at?->toISOString(),
            'notes' => $this->notes,
            'candidate' => CandidateResource::make($this->whenLoaded('candidate')),
            'job' => RecruitmentJobResource::make($this->whenLoaded('job')),
            'interviews' => InterviewResource::collection($this->whenLoaded('interviews')),
            'attempts' => TestAttemptResource::collection($this->whenLoaded('attempts')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

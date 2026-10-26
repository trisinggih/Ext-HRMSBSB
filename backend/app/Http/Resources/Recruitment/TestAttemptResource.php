<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'candidate_id' => $this->candidate_id,
            'application_id' => $this->application_id,
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'correct_count' => $this->correct_count,
            'passed' => !is_null($this->score),
            'test_name' => $this->whenLoaded('test', fn () => $this->test->name),
            'answers' => TestAnswerResource::collection($this->whenLoaded('answers')),
            'started_at' => $this->started_at?->toISOString(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

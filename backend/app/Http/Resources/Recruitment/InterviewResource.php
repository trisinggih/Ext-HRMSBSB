<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'mode' => $this->mode,
            'location' => $this->location,
            'meeting_link' => $this->meeting_link,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'interviewer_id' => $this->interviewer_id,
            'status' => $this->status,
            'result_notes' => $this->result_notes,
            'rating' => $this->rating,
            'decided' => $this->decided,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

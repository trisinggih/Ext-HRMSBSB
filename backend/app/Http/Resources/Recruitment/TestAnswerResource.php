<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_attempt_id' => $this->test_attempt_id,
            'question_id' => $this->question_id,
            'chosen_answer' => $this->chosen_answer,
            'is_correct' => (bool) $this->is_correct,
        ];
    }
}

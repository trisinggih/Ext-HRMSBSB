<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestQuestionResource extends JsonResource
{
    public bool $forCandidate = false;

    public function forCandidate(bool $value = true): static
    {
        $this->forCandidate = $value;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'question' => $this->question,
            'option_a' => $this->option_a,
            'option_b' => $this->option_b,
            'option_c' => $this->option_c,
            'option_d' => $this->option_d,
            'score' => $this->score,
            $this->mergeWhen(!$this->forCandidate, [
                'correct_answer' => $this->correct_answer,
            ]),
        ];
    }
}

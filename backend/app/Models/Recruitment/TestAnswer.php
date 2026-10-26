<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAnswer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'test_attempt_id', 'question_id', 'chosen_answer', 'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class);
    }
}

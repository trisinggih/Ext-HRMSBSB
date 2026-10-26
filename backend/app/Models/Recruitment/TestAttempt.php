<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAttempt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'test_id', 'candidate_id', 'application_id', 'score', 'total_questions',
        'correct_count', 'started_at', 'submitted_at', 'status',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_questions' => 'integer',
        'correct_count' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(CandidateApplication::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }
}

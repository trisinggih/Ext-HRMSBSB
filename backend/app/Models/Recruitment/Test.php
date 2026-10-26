<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'recruitment_job_id', 'name', 'description', 'duration_minutes',
        'passing_score', 'status',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'status' => 'boolean',
    ];

    public function recruitmentJob(): BelongsTo
    {
        return $this->belongsTo(RecruitmentJob::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TestQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }
}

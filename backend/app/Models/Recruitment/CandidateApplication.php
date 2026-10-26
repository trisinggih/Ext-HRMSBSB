<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'candidate_id', 'recruitment_job_id', 'status', 'applied_at', 'notes',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RecruitmentJob::class, 'recruitment_job_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'application_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class, 'application_id');
    }
}

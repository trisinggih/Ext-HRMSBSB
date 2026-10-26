<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_id', 'mode', 'location', 'meeting_link', 'scheduled_at',
        'interviewer_id', 'status', 'result_notes', 'rating', 'decided',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'rating' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CandidateApplication::class);
    }
}

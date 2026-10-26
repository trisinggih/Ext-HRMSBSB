<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'candidate_id', 'document_type', 'file_path', 'original_name',
        'is_verified', 'notes',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}

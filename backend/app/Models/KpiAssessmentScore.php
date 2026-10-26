<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiAssessmentScore extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'assessment_id', 'template_indicator_id', 'rating_level_id',
        'raw_score', 'weighted_score', 'notes',
    ];

    protected $casts = [
        'raw_score' => 'decimal:2',
        'weighted_score' => 'decimal:2',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(KpiAssessment::class, 'assessment_id');
    }

    public function templateIndicator(): BelongsTo
    {
        return $this->belongsTo(KpiTemplateIndicator::class, 'template_indicator_id');
    }

    public function ratingLevel(): BelongsTo
    {
        return $this->belongsTo(KpiRatingLevel::class, 'rating_level_id');
    }
}

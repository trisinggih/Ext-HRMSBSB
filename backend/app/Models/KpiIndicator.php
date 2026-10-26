<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiIndicator extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'name', 'code', 'description', 'category', 'type',
        'measurement_unit', 'rating_scale_id', 'is_active',
        'frequency', 'calculation_method', 'scoring_points',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'scoring_points' => 'array',
    ];

    public function ratingScale(): BelongsTo
    {
        return $this->belongsTo(KpiRatingScale::class, 'rating_scale_id');
    }

    public function templateIndicators(): HasMany
    {
        return $this->hasMany(KpiTemplateIndicator::class, 'indicator_id');
    }
}

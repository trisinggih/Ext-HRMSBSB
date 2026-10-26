<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplate extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'name', 'description', 'scope', 'position_id', 'division_id',
        'rating_scale_id', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function ratingScale(): BelongsTo
    {
        return $this->belongsTo(KpiRatingScale::class, 'rating_scale_id');
    }

    public function templateIndicators(): HasMany
    {
        return $this->hasMany(KpiTemplateIndicator::class, 'template_id')->orderBy('sort_order');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(KpiAssessment::class, 'template_id');
    }
}

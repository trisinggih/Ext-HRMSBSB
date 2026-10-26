<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplateIndicator extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = ['template_id', 'indicator_id', 'weight', 'target', 'sort_order', 'frequency', 'calculation_method', 'scoring_points'];
    protected $casts = [
        'weight' => 'decimal:2',
        'scoring_points' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'template_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(KpiIndicator::class, 'indicator_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(KpiAssessmentScore::class, 'template_indicator_id');
    }
}

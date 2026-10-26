<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiRatingLevel extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = ['rating_scale_id', 'label', 'score', 'description', 'sort_order'];
    protected $casts = ['score' => 'decimal:2'];

    public function scale(): BelongsTo
    {
        return $this->belongsTo(KpiRatingScale::class, 'rating_scale_id');
    }
}

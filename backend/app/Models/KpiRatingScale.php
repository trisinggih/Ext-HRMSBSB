<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiRatingScale extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function levels(): HasMany
    {
        return $this->hasMany(KpiRatingLevel::class, 'rating_scale_id')->orderBy('sort_order');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(KpiIndicator::class, 'rating_scale_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(KpiTemplate::class, 'rating_scale_id');
    }
}

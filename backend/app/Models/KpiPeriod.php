<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiPeriod extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'name', 'type', 'department_group', 'start_date', 'end_date',
        'review_start_date', 'review_end_date', 'status', 'notes',
    ];

    public function assessments(): HasMany
    {
        return $this->hasMany(KpiAssessment::class, 'period_id');
    }
}

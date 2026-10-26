<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'break_minutes',
        'working_hours',
        'late_tolerance_minutes',
        'max_overtime_request_time',
        'overtime_rules',
        'cross_midnight',
        'is_active',
    ];

    protected $casts = [
        'break_minutes' => 'integer',
        'working_hours' => 'integer',
        'late_tolerance_minutes' => 'integer',
        'overtime_rules' => 'array',
        'cross_midnight' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($shift) {
            if ($shift->clock_in && $shift->clock_out && $shift->clock_in > $shift->clock_out) {
                $shift->cross_midnight = true;
            }
        });
    }

    public function getIsOvernightAttribute(): bool
    {
        return (bool) ($this->cross_midnight || ($this->clock_in && $this->clock_out && $this->clock_in > $this->clock_out));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}

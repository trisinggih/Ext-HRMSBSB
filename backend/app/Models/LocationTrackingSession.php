<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class LocationTrackingSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'mode',
        'started_at',
        'stopped_at',
        'is_active',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function locations()
    {
        return $this->hasMany(EmployeeLocation::class, 'session_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class EmployeeLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'mode',
        'latitude',
        'longitude',
        'accuracy',
        'recorded_at',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function session()
    {
        return $this->belongsTo(LocationTrackingSession::class);
    }
}

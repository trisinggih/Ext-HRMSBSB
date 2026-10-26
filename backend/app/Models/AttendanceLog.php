<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id', 'attendance_id', 'action', 'action_at',
        'latitude', 'longitude', 'accuracy', 'device_info', 'is_face_verified',
    ];

    protected $casts = [
        'action_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'decimal:2',
        'is_face_verified' => 'boolean',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

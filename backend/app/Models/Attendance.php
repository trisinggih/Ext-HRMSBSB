<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id', 'shift_id', 'date',
        'clock_in_at', 'clock_out_at', 'break_start_at', 'break_end_at',
        'overtime_clock_in_at', 'overtime_clock_out_at',
        'break_minutes', 'working_hours',
        'clock_in_lat', 'clock_in_lng', 'clock_in_accuracy',
        'clock_out_lat', 'clock_out_lng',
        'clock_in_photo', 'clock_out_photo',
        'late_minutes', 'overtime_minutes', 'status', 'notes', 'attachment', 'is_face_verified',
        'overtime_report_text', 'overtime_report_photo', 'employee_assignment_id',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'break_start_at' => 'datetime',
        'break_end_at' => 'datetime',
        'overtime_clock_in_at' => 'datetime',
        'overtime_clock_out_at' => 'datetime',
        'break_minutes' => 'integer',
        'working_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'is_face_verified' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeAssignment::class, 'employee_assignment_id');
    }
}

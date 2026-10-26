<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateRequest extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id', 'attendance_id', 'date',
        'scheduled_clock_in', 'actual_clock_in', 'late_minutes',
        'reason', 'attachment', 'status',
    ];

    protected $casts = [
        'date' => 'date',
        'late_minutes' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approval()
    {
        return $this->hasOne(ApprovalRequest::class, 'module_id', 'id')
            ->where('module', 'late');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'date',
        'reason',
        'requested_start_time',
        'requested_end_time',
        'status',
        'supervisor_approved_at',
        'hr_approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'supervisor_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function approval()
    {
        return $this->hasOne(ApprovalRequest::class, 'module_id', 'id')
            ->where('module', 'overtime');
    }
}

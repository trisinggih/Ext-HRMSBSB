<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date',
        'total_days', 'reason', 'attachment', 'status',
        'approved_at', 'rejected_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:1',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approval()
    {
        return $this->hasOne(ApprovalRequest::class, 'module_id', 'id')
            ->where('module', 'leave');
    }
}

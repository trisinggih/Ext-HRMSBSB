<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwapRequest extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id', 'target_employee_id', 'shift_id', 'swap_date',
        'status', 'reason',
        'target_confirmed_at', 'supervisor_approved_at', 'hr_approved_at',
    ];

    protected $casts = [
        'swap_date' => 'date',
        'target_confirmed_at' => 'datetime',
        'supervisor_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function targetEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function approval()
    {
        return $this->hasOne(ApprovalRequest::class, 'module_id', 'id')
            ->where('module', 'shift_swap');
    }
}

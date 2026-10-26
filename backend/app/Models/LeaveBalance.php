<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'year',
        'quota', 'used', 'carried_forward', 'expired', 'remaining',
    ];

    protected $casts = [
        'year' => 'integer',
        'quota' => 'integer',
        'used' => 'integer',
        'carried_forward' => 'integer',
        'expired' => 'integer',
        'remaining' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Sisa kuota yang masih bisa dipakai tahun ini.
     */
    public function available(): float
    {
        return (float) ($this->quota + ($this->is_carry_forward ? $this->carried_forward : 0) - $this->used);
    }
}

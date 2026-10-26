<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeApprovalDelegation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'delegator_id',
        'delegate_id',
        'module',
        'start_date',
        'end_date',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delegator_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delegate_id');
    }

    /**
     * Cek apakah delegasi ini masih aktif pada tanggal sekarang.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('start_date', '<=', now()->toDateString())
                     ->where('end_date', '>=', now()->toDateString());
    }
}

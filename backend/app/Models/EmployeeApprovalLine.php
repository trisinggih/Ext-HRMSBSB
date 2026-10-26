<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeApprovalLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'module',
        'step_order',
        'approver_id',
        'is_optional',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}

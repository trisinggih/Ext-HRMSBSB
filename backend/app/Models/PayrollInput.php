<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollInput extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_period_id', 'employee_id', 'payroll_component_id',
        'amount', 'quantity', 'note', 'source', 'reference_type', 'reference_id',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'quantity' => 'float',
        'approved_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }
}

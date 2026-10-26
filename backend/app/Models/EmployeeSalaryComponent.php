<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryComponent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'employee_assignment_id', 'payroll_component_id', 'amount', 'percentage',
        'calculation_type', 'formula',
        'effective_from', 'is_active',
    ];

    protected $casts = [
        'amount' => 'float',
        'percentage' => 'float',
        'effective_from' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}

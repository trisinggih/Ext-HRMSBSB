<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_period_id', 'employee_id', 'employee_assignment_id', 'run_type', 'salary_cycle_type',
        'gross_earnings', 'total_benefit',
        'gross_salary', 'total_allowance', 'total_overtime', 'total_bonus',
        'total_commission', 'total_reward', 'total_deduction', 'total_tax',
        'total_bpjs', 'net_salary', 'breakdown', 'status', 'calculated_at',
        'email_sent_at', 'email_sent_by',
    ];

    protected $casts = [
        'gross_earnings' => 'float',
        'total_benefit' => 'float',
        'gross_salary' => 'float',
        'total_allowance' => 'float',
        'total_overtime' => 'float',
        'total_bonus' => 'float',
        'total_commission' => 'float',
        'total_reward' => 'float',
        'total_deduction' => 'float',
        'total_tax' => 'float',
        'total_bpjs' => 'float',
        'net_salary' => 'float',
        'breakdown' => 'array',
        'calculated_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeAssignment::class, 'employee_assignment_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class, 'payroll_run_id');
    }
}

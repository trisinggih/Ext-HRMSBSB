<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryIncreaseProposal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'probation_evaluation_id',
        'employee_id',
        'proposal_date',
        'employment_duration_text',
        'current_daily_salary',
        'current_monthly_salary',
        'current_attendance_bonus',
        'current_position_allowance',
        'current_leader_allowance',
        'proposed_daily_salary_increase',
        'proposed_monthly_salary_increase',
        'proposed_attendance_bonus_increase',
        'proposed_position_allowance_increase',
        'proposed_leader_allowance_increase',
        'new_daily_salary',
        'new_monthly_salary',
        'new_attendance_bonus',
        'new_position_allowance',
        'new_leader_allowance',
        'reasons',
        'status',
        'proposed_by',
        'area_manager_id',
        'accounting_manager_id',
        'hrd_manager_id',
        'director_id',
    ];

    protected $casts = [
        'proposal_date' => 'date',
        'reasons' => 'array',
        'current_daily_salary' => 'decimal:2',
        'current_monthly_salary' => 'decimal:2',
        'current_attendance_bonus' => 'decimal:2',
        'current_position_allowance' => 'decimal:2',
        'current_leader_allowance' => 'decimal:2',
        'proposed_daily_salary_increase' => 'decimal:2',
        'proposed_monthly_salary_increase' => 'decimal:2',
        'proposed_attendance_bonus_increase' => 'decimal:2',
        'proposed_position_allowance_increase' => 'decimal:2',
        'proposed_leader_allowance_increase' => 'decimal:2',
        'new_daily_salary' => 'decimal:2',
        'new_monthly_salary' => 'decimal:2',
        'new_attendance_bonus' => 'decimal:2',
        'new_position_allowance' => 'decimal:2',
        'new_leader_allowance' => 'decimal:2',
    ];

    public function probationEvaluation()
    {
        return $this->belongsTo(ProbationEvaluation::class, 'probation_evaluation_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function proposedBy()
    {
        return $this->belongsTo(Employee::class, 'proposed_by');
    }

    public function areaManager()
    {
        return $this->belongsTo(Employee::class, 'area_manager_id');
    }

    public function accountingManager()
    {
        return $this->belongsTo(Employee::class, 'accounting_manager_id');
    }

    public function hrdManager()
    {
        return $this->belongsTo(Employee::class, 'hrd_manager_id');
    }

    public function director()
    {
        return $this->belongsTo(Employee::class, 'director_id');
    }
}

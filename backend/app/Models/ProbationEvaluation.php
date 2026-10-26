<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProbationEvaluation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'evaluation_date',
        'probation_start_date',
        'probation_end_date',
        'probation_period_text',
        'absent_days',
        'permit_days',
        'sick_days',
        'half_days',
        'late_days',
        'has_oral_warning',
        'has_sp1',
        'has_sp2',
        'aspect_scores',
        'total_weighted_score',
        'final_score_hundred',
        'predicate',
        'strengths',
        'improvements',
        'final_decision',
        'decision_notes',
        'status',
        'approved_by_dept_manager',
        'approved_by_hrd_manager',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'probation_start_date' => 'date',
        'probation_end_date' => 'date',
        'has_oral_warning' => 'boolean',
        'has_sp1' => 'boolean',
        'has_sp2' => 'boolean',
        'aspect_scores' => 'array',
        'total_weighted_score' => 'decimal:2',
        'final_score_hundred' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    public function approvedByDeptManager()
    {
        return $this->belongsTo(Employee::class, 'approved_by_dept_manager');
    }

    public function approvedByHrdManager()
    {
        return $this->belongsTo(Employee::class, 'approved_by_hrd_manager');
    }

    public function salaryProposal()
    {
        return $this->hasOne(SalaryIncreaseProposal::class, 'probation_evaluation_id');
    }
}

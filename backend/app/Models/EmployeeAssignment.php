<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'company_id', 'placement_company_id', 'branch_id', 'department_id',
        'division_id', 'position_id', 'supervisor_id', 'join_date', 'end_date', 'nip',
        'employment_status', 'is_primary', 'salary_cycle_type'
    ];

    protected $casts = [
        'join_date' => 'date',
        'end_date' => 'date',
        'is_primary' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function placementCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'placement_company_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function payrollComponents()
    {
        return $this->belongsToMany(PayrollComponent::class, 'employee_assignment_payroll_components', 'employee_assignment_id', 'payroll_component_id');
    }
}

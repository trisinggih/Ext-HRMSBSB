<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmployeeMutation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'memo_number',
        'employee_id',
        'type',
        'old_company_id',
        'old_branch_id',
        'old_department_id',
        'old_division_id',
        'old_position_id',
        'old_supervisor_id',
        'new_company_id',
        'new_branch_id',
        'new_department_id',
        'new_division_id',
        'new_position_id',
        'new_supervisor_id',
        'effective_date',
        'new_nip',
        'prorate_payroll',
        'status',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'prorate_payroll' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function oldCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'old_company_id');
    }

    public function oldBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'old_branch_id');
    }

    public function oldDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'old_department_id');
    }

    public function oldDivision(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'old_division_id');
    }

    public function oldPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'old_position_id');
    }

    public function oldSupervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'old_supervisor_id');
    }

    public function newCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'new_company_id');
    }

    public function newBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'new_branch_id');
    }

    public function newDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'new_department_id');
    }

    public function newDivision(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'new_division_id');
    }

    public function newPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'new_position_id');
    }

    public function newSupervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'new_supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'record');
    }
}

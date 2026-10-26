<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use SoftDeletes;

    protected $table = 'salary_structures';

    protected $fillable = [
        'employee_id',
        'payroll_component_id',
        'amount',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'date',
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

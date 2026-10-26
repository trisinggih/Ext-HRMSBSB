<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructureComponent extends Model
{
    use SoftDeletes;

    protected $fillable = ['salary_structure_id', 'payroll_component_id', 'amount'];

    protected $casts = [
        'amount' => 'float',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}

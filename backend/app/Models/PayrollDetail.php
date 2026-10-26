<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_run_id', 'payroll_component_id', 'payroll_rule_id', 
        'label', 'type', 'quantity', 'rate', 'base_amount', 'amount',
        'source', 'reference_type', 'reference_id', 'calculation_snapshot', 'note',
    ];

    protected $casts = [
        'quantity' => 'float',
        'rate' => 'float',
        'base_amount' => 'float',
        'amount' => 'float',
        'calculation_snapshot' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}

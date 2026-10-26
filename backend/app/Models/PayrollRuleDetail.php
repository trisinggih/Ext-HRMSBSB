<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRuleDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_rule_id', 'min_value', 'max_value', 'rate', 'fixed_amount',
        'formula', 'priority',
    ];

    protected $casts = [
        'min_value' => 'float',
        'max_value' => 'float',
        'rate' => 'float',
        'fixed_amount' => 'float',
        'priority' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PayrollRule::class, 'payroll_rule_id');
    }
}

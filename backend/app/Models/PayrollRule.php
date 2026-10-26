<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_component_id', 'name', 'code', 'source', 'method',
        'condition', 'formula', 'priority', 'effective_from', 'effective_to', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'priority' => 'integer',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollRuleDetail::class, 'payroll_rule_id');
    }
}

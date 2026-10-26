<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRule extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'period', 'effective_date', 'is_active', 'rate'];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
        'rate' => 'float',
    ];

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(PayrollComponent::class, 'tax_rule_components');
    }
}

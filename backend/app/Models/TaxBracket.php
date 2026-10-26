<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxBracket extends Model
{
    use SoftDeletes;

    protected $fillable = ['tax_rule_id', 'min_amount', 'max_amount', 'rate'];

    protected $casts = [
        'min_amount' => 'float',
        'max_amount' => 'float',
        'rate' => 'float',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class, 'tax_rule_id');
    }
}

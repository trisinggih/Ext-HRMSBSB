<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class TaxTerRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category',
        'min_bruto',
        'max_bruto',
        'rate',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'min_bruto' => 'float',
        'max_bruto' => 'float',
        'rate' => 'float',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];
}

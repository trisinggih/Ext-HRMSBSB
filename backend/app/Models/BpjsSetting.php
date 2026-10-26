<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class BpjsSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bpjs_type', 'name', 'employee_rate', 'company_rate',
        'max_limit', 'effective_date', 'is_active', 'is_tax_deductible',
    ];

    protected $casts = [
        'employee_rate' => 'float',
        'company_rate' => 'float',
        'max_limit' => 'float',
        'effective_date' => 'date',
        'is_active' => 'boolean',
        'is_tax_deductible' => 'boolean',
    ];
}

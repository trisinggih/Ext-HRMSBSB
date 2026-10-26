<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualBonusRule extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'payroll_component_id',
        'distribution_month',
        'is_prorated',
    ];

    protected $casts = [
        'is_prorated' => 'boolean',
    ];

    public function component()
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxEmployeeSetting extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'tax_rule_id', 'status', 'npwp_certificate', 'dependents'];

    protected $casts = [
        'npwp_certificate' => 'integer',
        'dependents' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class, 'tax_rule_id');
    }
}

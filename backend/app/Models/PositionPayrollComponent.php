<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionPayrollComponent extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'position_id', 'payroll_component_id', 'fixed_amount',
    ];

    protected $casts = [
        'fixed_amount' => 'decimal:2',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}

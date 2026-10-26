<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'payroll_period_id', 'type', 'title', 'description', 'amount', 'reward_date', 'attachment',
    ];

    protected function casts(): array
    {
        return [
            'reward_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }
}

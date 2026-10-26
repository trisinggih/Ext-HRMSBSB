<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class EmployeeRecurringIncentive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'type',
        'title',
        'description',
        'amount',
        'payout_day_of_month',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
            'payout_day_of_month' => 'integer',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

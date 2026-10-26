<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'period', 'start_date', 'end_date', 'payment_date',
        'status', 'processed_by', 'processed_at', 'company_id', 'branch_id', 'cycle_type'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'processed_at' => 'datetime',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class, 'payroll_period_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'payroll_period_id');
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class, 'payroll_period_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayrollSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'pay_frequency',
        'bank_id', 'bank_account', 'bank_account_name',
        'max_loan_limit',
    ];

    protected $casts = [
        'max_loan_limit' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

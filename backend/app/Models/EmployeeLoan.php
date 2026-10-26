<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id',
        'loan_request_id',
        'total_loan',
        'remaining_balance',
        'installment_amount',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function loanRequest()
    {
        return $this->belongsTo(LoanRequest::class, 'loan_request_id');
    }
}

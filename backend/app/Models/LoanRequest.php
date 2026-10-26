<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRequest extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'amount',
        'installment_months',
        'reason',
        'status',
        'approved_at',
        'rejected_at',
        'attachment_url',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function approval()
    {
        return $this->hasOne(ApprovalRequest::class, 'module_id', 'id')
            ->where('module', 'loan_request');
    }

    public function loan()
    {
        return $this->hasOne(EmployeeLoan::class, 'loan_request_id');
    }
}

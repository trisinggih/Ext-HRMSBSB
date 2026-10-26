<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reimbursement extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'type',
        'amount',
        'description',
        'attachment',
        'status',
        'approved_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approval()
    {
        return $this->hasOne(ApprovalRequest::class, 'module_id', 'id')
            ->where('module', 'reimbursement');
    }
}

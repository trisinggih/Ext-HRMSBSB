<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class EmployeeCertification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'name',
        'issuer',
        'issue_date',
        'expiry_date',
        'credential_id',
        'credential_url',
        'description',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

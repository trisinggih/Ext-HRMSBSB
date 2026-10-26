<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class EmployeeTraining extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'training_name',
        'organizer',
        'start_date',
        'end_date',
        'certificate_number',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

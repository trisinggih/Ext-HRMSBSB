<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{
    use SoftDeletes;

    protected $table = 'employee_education';

    protected $fillable = [
        'employee_id',
        'level',
        'institution_name',
        'major',
        'start_year',
        'end_year',
        'gpa',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

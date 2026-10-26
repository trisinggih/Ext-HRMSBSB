<?php

namespace App\Observers;

use App\Models\Employee;

class EmployeeObserver
{
    public function created(Employee $employee)
    {
        $employee->recalculatePtkpStatus();
    }

    public function updated(Employee $employee)
    {
        if ($employee->isDirty('marital_status')) {
            $employee->recalculatePtkpStatus();
        }
    }
}

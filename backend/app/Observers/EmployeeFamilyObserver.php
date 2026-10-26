<?php

namespace App\Observers;

use App\Models\EmployeeFamily;

class EmployeeFamilyObserver
{
    public function created(EmployeeFamily $family)
    {
        if ($family->is_tax_dependent) {
            $family->employee->recalculatePtkpStatus();
        }
    }

    public function updated(EmployeeFamily $family)
    {
        if ($family->isDirty('is_tax_dependent')) {
            $family->employee->recalculatePtkpStatus();
        }
    }

    public function deleted(EmployeeFamily $family)
    {
        if ($family->is_tax_dependent) {
            $family->employee->recalculatePtkpStatus();
        }
    }
}

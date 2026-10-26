<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$assignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'DAILY_PAID_MONTHLY')->first();
$employee = $assignment->employee;

$kerajinan = App\Models\PayrollComponent::where('code', 'kerajinan')->first();
echo "Kerajinan ID: " . $kerajinan->id . "\n";

$assigned = App\Models\EmployeeSalaryComponent::where('employee_id', $employee->id)->where('payroll_component_id', $kerajinan->id)->first();
if ($assigned) {
    echo "Assigned to employee: Yes\n";
} else {
    echo "Assigned to employee: No\n";
}

$pivotAssigned = $assignment->payrollComponents()->where('payroll_component_id', $kerajinan->id)->first();
if ($pivotAssigned) {
    echo "Assigned to pivot: Yes\n";
} else {
    echo "Assigned to pivot: No\n";
}


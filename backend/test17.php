<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$assignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'DAILY_PAID_MONTHLY')->first();
$employee = $assignment->employee;
$comp = App\Models\PayrollComponent::where('code', 'kerajinan')->first();

$row = App\Models\EmployeeSalaryComponent::where('employee_id', $employee->id)->where('payroll_component_id', $comp->id)->first();
echo "row calculation_type: " . $row->calculation_type . "\n";

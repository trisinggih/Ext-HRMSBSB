<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$assignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'DAILY_PAID_MONTHLY')->first();
$employee = $assignment->employee;
$components = App\Models\EmployeeSalaryComponent::where('employee_id', $employee->id)->get();

echo "Employee {$employee->name} has " . $components->count() . " components.\n";
foreach ($components as $c) {
    echo "- Component ID {$c->payroll_component_id} | Amount {$c->amount} | Active: {$c->is_active} | Effective: {$c->effective_from}\n";
}

$pivot = $assignment->payrollComponents()->get();
echo "\nPivot components: " . $pivot->count() . "\n";
foreach ($pivot as $p) {
    echo "- Component ID {$p->id} | {$p->name}\n";
}

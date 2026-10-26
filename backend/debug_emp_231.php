<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculatorService;

$emp = Employee::find(231);
if (!$emp) {
    echo "Employee 231 not found!\n";
    exit;
}

echo "Employee: {$emp->name} (ID: {$emp->id})\n";

$assignments = EmployeeAssignment::where('employee_id', 231)->get();
echo "Assignments count: " . count($assignments) . "\n";
foreach ($assignments as $a) {
    echo "Assignment ID: {$a->id} | IsPrimary: {$a->is_primary} | CycleType: {$a->salary_cycle_type}\n";
}

$esc = EmployeeSalaryComponent::where('employee_id', 231)->with('component')->get();
echo "\nAll Employee Salary Components for Emp 231 (" . count($esc) . " components):\n";
foreach ($esc as $e) {
    $c = $e->component;
    if (!$c) {
        echo "Row ID: {$e->id} | PayrollCompID: {$e->payroll_component_id} | Component NOT FOUND!\n";
        continue;
    }
    $schema = is_array($c->payment_schema) ? json_encode($c->payment_schema) : ($c->payment_schema ?? 'null');
    echo "CompID: {$c->id} | Code: {$c->code} | Name: {$c->name} | Active: {$c->is_active} | AssignmentID: {$e->employee_assignment_id} | CalcType: {$e->calculation_type} | Amount: {$e->amount} | Schema: {$schema}\n";
}

echo "\n--- TESTING SIMULATION FOR EMP 231 ---\n";
$primaryAssignment = $assignments->where('is_primary', true)->first() ?? $assignments->first();

$service = app(PayrollCalculatorService::class);
$period = new PayrollPeriod([
    'start_date' => '2026-09-01',
    'end_date' => '2026-09-30',
    'cycle_type' => 'daily_paid_monthly',
]);

$res = $service->calculateForAssignment($emp, $primaryAssignment, $period, [
    'working_days' => 30,
    'present_days' => 30,
    'absent_days' => 0,
    'late_minutes' => 0,
    'late_occurrences' => 0,
    'overtime_hours' => 0,
    'day_off' => 0,
    'days_in_month' => 30,
]);

echo "Simulation output details count: " . count($res['_details']) . "\n";
foreach ($res['_details'] as $item) {
    $label = $item['label'] ?? $item['name'] ?? 'N/A';
    $amount = $item['amount'] ?? 0;
    echo "- {$label}: Rp " . number_format($amount, 2, ',', '.') . "\n";
}

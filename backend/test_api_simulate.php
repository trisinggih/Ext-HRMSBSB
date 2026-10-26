<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Illuminate\Http\Request;

$employee = Employee::find(231);
$assignment = EmployeeAssignment::where('employee_id', 231)->where('is_primary', true)->first() ?? EmployeeAssignment::where('employee_id', 231)->first();

echo "Employee: {$employee->name} (ID: {$employee->id})\n";
echo "Assignment ID: {$assignment->id}, SalaryCycleType: {$assignment->salary_cycle_type}\n";

$controller = app(\App\Http\Controllers\Api\v1\Payroll\PayrollController::class);

$req = Request::create('/api/v1/payroll/simulate/231', 'POST', [
    'cycle_type' => 'monthly_payment',
    'days_in_month' => 30,
    'day_off' => 0,
    'present_days' => 30,
    'absent_days' => 0,
    'late_minutes' => 0,
    'late_occurrences' => 0,
    'overtime_hours' => 0,
    'working_days' => 30,
    'assignment_id' => $assignment->id,
]);

$response = $controller->simulate($employee, $req);
$content = json_decode($response->getContent(), true);

echo "\nAPI Simulation Response:\n";
print_r($content);

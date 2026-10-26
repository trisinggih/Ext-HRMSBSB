<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find an employee with DAILY_PAID_MONTHLY
$assignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'DAILY_PAID_MONTHLY')->first();

if (!$assignment) {
    echo "No DAILY_PAID_MONTHLY assignment found.\n";
    exit;
}
$employee = $assignment->employee;

echo "Employee: " . $employee->name . "\n";

$period = new App\Models\PayrollPeriod([
    'start_date' => '2026-10-01',
    'end_date' => '2026-10-31',
    'cycle_type' => 'monthly',
]);

$service = app(App\Services\PayrollCalculatorService::class);
$result = $service->calculateForAssignment($employee, $assignment, $period);

echo "Bonus Kerajinan Amount: " . collect($result['earnings'] ?? [])->where('code', 'kerajinan')->sum('amount') . "\n";
echo "Let's inspect variables... wait, PayrollCalculatorService doesn't return variables directly, so I'll recreate the logic here.\n";

$attendances = App\Models\Attendance::where('employee_id', $employee->id)
    ->whereBetween('date', ['2026-10-01', '2026-10-31'])
    ->get();

$totalAbsentDays = $attendances->where('status', 'absent')->count();
$totalDayOff = $attendances->where('status', 'day_off')->count();
$totalAttendanceDays = $attendances->whereIn('status', ['present', 'late', 'early_leave'])->count();
$daysInMonth = 31;
$workDays = max(1, $daysInMonth - $totalDayOff);

echo "Total Attendances: " . count($attendances) . "\n";
echo "Total Absent: $totalAbsentDays\n";
echo "Total Day Off: $totalDayOff\n";
echo "Total Present/Late: $totalAttendanceDays\n";
echo "Days In Month: $daysInMonth\n";
echo "Work Days (calculated): $workDays\n";

$missingDays = max(0, $workDays - $totalAttendanceDays);
$unpaid = $totalAbsentDays;
$totalAbsenEffectif = max($missingDays, $unpaid);

echo "Missing Days: $missingDays\n";
echo "Effective Absences: $totalAbsenEffectif\n";

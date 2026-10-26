<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$assignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'DAILY_PAID_MONTHLY')->first();
$employee = $assignment->employee;

$period = new App\Models\PayrollPeriod([
    'start_date' => '2026-10-01',
    'end_date' => '2026-10-31',
    'cycle_type' => 'monthly',
]);

$service = app(App\Services\PayrollCalculatorService::class);
// pass full attendance array: 26 working days, 26 present, 0 absent, 4 day off
$assume = [
    'working_days' => 26,
    'present_days' => 26,
    'absent_days' => 0,
    'day_off' => 4, // THIS is the key
    'days_in_month' => 31,
];
$result = $service->calculateForAssignment($employee, $assignment, $period, $assume);

echo "Bonus Kerajinan Amount (with 26 attend, 4 day off): " . collect($result['earnings'] ?? [])->where('code', 'kerajinan')->sum('amount') . "\n";

// what if day_off is 0?
$assume['day_off'] = 0;
$result2 = $service->calculateForAssignment($employee, $assignment, $period, $assume);
echo "Bonus Kerajinan Amount (with 26 attend, 0 day off): " . collect($result2['earnings'] ?? [])->where('code', 'kerajinan')->sum('amount') . "\n";


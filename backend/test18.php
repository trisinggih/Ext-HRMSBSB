<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$comp = App\Models\PayrollComponent::where('code', 'kerajinan')->first();
$comp->has_rules = 0;
$comp->formula = null;
$comp->save();

echo "Cleared has_rules and formula from kerajinan component.\n";

$assignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'DAILY_PAID_MONTHLY')->first();
$employee = $assignment->employee;
$period = new App\Models\PayrollPeriod([
    'start_date' => '2026-10-01',
    'end_date' => '2026-10-31',
    'cycle_type' => 'monthly',
]);

$service = app(App\Services\PayrollCalculatorService::class);
$assume = [
    'working_days' => 26,
    'present_days' => 26,
    'absent_days' => 0,
    'day_off' => 4,
    'days_in_month' => 31,
];
$result = $service->calculateForAssignment($employee, $assignment, $period, $assume);

foreach ($result['earnings'] ?? [] as $comp) {
    if ($comp['code'] === 'kerajinan') {
        echo "Kerajinan Amount: " . $comp['amount'] . "\n";
    }
}

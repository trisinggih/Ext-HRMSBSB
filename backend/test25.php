<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\PayrollCalculatorService::class);
$period = new App\Models\PayrollPeriod([
    'start_date' => '2026-10-01',
    'end_date' => '2026-10-31',
    'cycle_type' => 'monthly',
]);

// Test for DAILY_PAID_MONTHLY (Reguler vs Probation)
$assignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'DAILY_PAID_MONTHLY')->first();
$employee = $assignment->employee;

echo "=== DAILY_PAID_MONTHLY (REGULER / PERMANENT) ===\n";
$assignment->employment_status = 'permanent';
foreach ([0, 1, 2, 3, 4] as $absent) {
    $assume = [
        'working_days' => 26,
        'present_days' => 26 - $absent,
        'absent_days' => $absent,
        'day_off' => 0,
        'days_in_month' => 31,
    ];
    $res = $service->calculateForAssignment($employee, $assignment, $period, $assume);
    $k = collect($res['breakdown']['components'] ?? [])->firstWhere('code', 'kerajinan');
    echo "Absent {$absent} -> Kerajinan: " . ($k['amount'] ?? 'not found') . "\n";
}

echo "\n=== DAILY_PAID_MONTHLY (STATUS PERCOBAAN / PROBATION) ===\n";
$assignment->employment_status = 'probation';
foreach ([0, 1, 2, 3, 4] as $absent) {
    $assume = [
        'working_days' => 26,
        'present_days' => 26 - $absent,
        'absent_days' => $absent,
        'day_off' => 0,
        'days_in_month' => 31,
    ];
    $res = $service->calculateForAssignment($employee, $assignment, $period, $assume);
    $k = collect($res['breakdown']['components'] ?? [])->firstWhere('code', 'kerajinan');
    echo "Absent {$absent} -> Kerajinan: " . ($k['amount'] ?? 'not found') . "\n";
}


$monthlyAssignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'MONTHLY_PAID_MONTHLY')->first();
if ($monthlyAssignment) {
    $mEmp = $monthlyAssignment->employee;

    echo "\n=== MONTHLY_PAID_MONTHLY (REGULER / PERMANENT) ===\n";
    $monthlyAssignment->employment_status = 'permanent';
    foreach ([0, 1, 2, 3, 4] as $absent) {
        $assume = [
            'working_days' => 26,
            'present_days' => 26 - $absent,
            'absent_days' => $absent,
            'unpaid_leave_days' => $absent,
            'total_unpaid_leave_days' => $absent,
            'day_off' => 0,
            'days_in_month' => 31,
        ];
        $res = $service->calculateForAssignment($mEmp, $monthlyAssignment, $period, $assume);
        $k = collect($res['breakdown']['components'] ?? [])->firstWhere('code', 'kerajinan');
        echo "Unpaid Leave {$absent} -> Kerajinan: " . ($k['amount'] ?? 'not found') . "\n";
    }

    echo "\n=== MONTHLY_PAID_MONTHLY (STATUS PERCOBAAN / PROBATION) ===\n";
    $monthlyAssignment->employment_status = 'probation';
    foreach ([0, 1, 2, 3, 4] as $absent) {
        $assume = [
            'working_days' => 26,
            'present_days' => 26 - $absent,
            'absent_days' => $absent,
            'unpaid_leave_days' => $absent,
            'total_unpaid_leave_days' => $absent,
            'day_off' => 0,
            'days_in_month' => 31,
        ];
        $res = $service->calculateForAssignment($mEmp, $monthlyAssignment, $period, $assume);
        $k = collect($res['breakdown']['components'] ?? [])->firstWhere('code', 'kerajinan');
        echo "Unpaid Leave {$absent} -> Kerajinan: " . ($k['amount'] ?? 'not found') . "\n";
    }
}

$formula = 'if ({is_probation} == 1 && {total_unpaid_leave_days} == 0) 200000 else if ({is_probation} == 1 && {total_unpaid_leave_days} == 1) 150000 else if ({is_probation} == 1 && {total_unpaid_leave_days} == 2) 100000 else if ({is_probation} == 1 && {total_unpaid_leave_days} == 3) 50000 else if ({is_probation} == 1) 0 else if ({total_unpaid_leave_days} == 0) 500000 else if ({total_unpaid_leave_days} == 1) 300000 else if ({total_unpaid_leave_days} == 2) 200000 else if ({total_unpaid_leave_days} == 3) 100000 else 0';

echo "\n=== DIRECT FORMULA TEST ===\n";
$ref = new ReflectionMethod($service, 'evalFormula');
$ref->setAccessible(true);

foreach ([0, 1] as $isProbation) {
    echo $isProbation ? "--- Probation (percobaan) ---\n" : "--- Non-Probation (tetap/kontrak) ---\n";
    foreach ([0, 1, 2, 3, 4] as $absent) {
        $matched = [];
        $val = $ref->invokeArgs($service, [$formula, ['is_probation' => $isProbation, 'total_unpaid_leave_days' => $absent], &$matched]);
        echo "Absent {$absent} -> Rp " . number_format($val, 0, ',', '.') . "\n";
    }
}




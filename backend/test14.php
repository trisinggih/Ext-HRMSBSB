<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$assignment = App\Models\EmployeeAssignment::where('salary_cycle_type', 'DAILY_PAID_MONTHLY')->first();
$employee = $assignment->employee;
$date = \Carbon\Carbon::parse('2026-10-31');
$variables = [];

$empRows = App\Models\EmployeeSalaryComponent::where('employee_id', $employee->id)
    ->where(function($q) use ($assignment) {
        $q->whereNull('employee_assignment_id')
          ->orWhere('employee_assignment_id', $assignment->id);
    })
    ->where('is_active', true)
    ->where('effective_from', '<=', $date->toDateString())
    ->with('component')
    ->get();

$mergedRows = collect($empRows);
echo "Initial rows: " . count($mergedRows) . "\n";
foreach($mergedRows as $r) {
    if ($r->component->code == 'kerajinan') echo "Found kerajinan initially\n";
}

$salaryType = $assignment->salary_cycle_type ?? null;
if ($salaryType) {
    $mergedRows = $mergedRows->filter(function($row) use ($salaryType) {
        $comp = $row->component;
        if (!$comp) return false;
        $schema = $comp->payment_schema;
        if ($schema === null) return true;
        if (is_array($schema) && count($schema) === 0) return false;
        return in_array($salaryType, (array) $schema);
    });
}
echo "After payment_schema filter: " . count($mergedRows) . "\n";
foreach($mergedRows as $r) {
    if ($r->component->code == 'kerajinan') echo "Found kerajinan after schema filter\n";
}

$period = new App\Models\PayrollPeriod([
    'start_date' => '2026-10-01',
    'end_date' => '2026-10-31',
    'cycle_type' => 'monthly',
]);
$isBonusOnly = false;
$shouldSkip = function($comp) use ($isBonusOnly, $date, $period, $assignment) {
    if (!$comp || !$comp->is_active) { echo "Skip kerajinan: not active\n"; return true; }
    if ($comp->effective_from && $comp->effective_from->gt($date)) { echo "Skip kerajinan: effective_from gt date\n"; return true; }
    if ($isBonusOnly && !$comp->is_separate_payslip) { echo "Skip kerajinan: isBonusOnly && !is_separate\n"; return true; }
    if (!$isBonusOnly && $comp->is_separate_payslip) { echo "Skip kerajinan: !isBonusOnly && is_separate\n"; return true; }

    if (isset($period) && $period->cycle_type) {
        $cycleType = $period->cycle_type;
        
        if ($assignment->salary_cycle_type === 'DAILY_PAID_DAILY' && $cycleType === 'daily_payment') {
            $code = strtolower($comp->code);
            $isHarianOnly = in_array($code, ['gaji_harian', 'gaji_harian_per_bulan', 'uang_lembur_harian', 'potongan_terlambat_harian', 'potongan_absen_harian']);
            if (!$isHarianOnly) return true;
        }

        if ($assignment->salary_cycle_type === 'DAILY_PAID_WEEKLY' && ($cycleType === 'weekly_payment' || $cycleType === 'weekly_payslip')) {
            $code = strtolower($comp->code);
            $isTunjangan = str_contains($code, 'tunjangan') || $code === 'kerajinan' || $code === 'gaji_bulanan';
            $isMonthlyDeduction = in_array($comp->type, ['tax', 'bpjs']) || $code === 'potongan_kasbon';
            
            $isMonthlySlipComponent = $isTunjangan || $isMonthlyDeduction;

            if ($cycleType === 'weekly_payment' && $isMonthlySlipComponent) return true;
            if ($cycleType === 'weekly_payslip' && !$isMonthlySlipComponent) return true;
        }
    }
    return false;
};

foreach ($mergedRows as $row) {
    $comp = $row->component;
    if ($comp->code == 'kerajinan') {
        echo "Checking shouldSkip for kerajinan...\n";
        $skip = $shouldSkip($comp);
        echo "shouldSkip returned: " . ($skip ? 'true' : 'false') . "\n";
        echo "show_on_payslip: " . ($comp->show_on_payslip ? 'true' : 'false') . "\n";
    }
}

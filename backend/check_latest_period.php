<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PayrollPeriod;
use App\Models\EmployeeSalaryComponent;

$period = PayrollPeriod::latest()->first();
echo "Latest PayrollPeriod in DB:\n";
print_r($period ? $period->toArray() : []);

$dateStr = $period ? ($period->end_date ?: $period->start_date) : now()->toDateString();
echo "\nDate evaluated in PayrollCalculatorService: {$dateStr}\n";

$rows = EmployeeSalaryComponent::where('employee_id', 231)
    ->where('is_active', true)
    ->where('effective_from', '<=', $dateStr)
    ->with('component')
    ->get();

echo "\nRows matching effective_from <= {$dateStr} (" . count($rows) . " rows):\n";
foreach ($rows as $r) {
    echo "ID: {$r->id} | CompCode: {$r->component->code} | EffectiveFrom: {$r->effective_from} | AssignID: {$r->employee_assignment_id}\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$localDb = DB::connection('mysql');

$components = $localDb->table('employee_salary_components')
    ->whereNotNull('employee_assignment_id')
    ->get();

$countInserted = 0;

foreach ($components as $comp) {
    // Check if pivot exists
    $exists = $localDb->table('employee_assignment_payroll_components')
        ->where('employee_assignment_id', $comp->employee_assignment_id)
        ->where('payroll_component_id', $comp->payroll_component_id)
        ->exists();

    if (!$exists) {
        $localDb->table('employee_assignment_payroll_components')->insert([
            'employee_assignment_id' => $comp->employee_assignment_id,
            'payroll_component_id' => $comp->payroll_component_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $countInserted++;
    }
}

echo "Done syncing pivot table. Inserted: $countInserted\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

config(['database.connections.remote' => [
    'driver' => 'mysql',
    'host' => '76.13.16.13',
    'port' => '3306',
    'database' => 'ext_hrms',
    'username' => 'hrms',
    'password' => 'Hrms123!Secure',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]]);

$remoteDb = DB::connection('remote');
$localDb = DB::connection('mysql');

$remoteComponents = $remoteDb->table('employee_salary_components')->get();

// Load mappings
$remoteEmployees = $remoteDb->table('employees')->select('id', 'employee_id')->get()->keyBy('id');
$localEmployees = $localDb->table('employees')->select('id', 'employee_id')->get()->keyBy('employee_id');

$remoteAssignments = $remoteDb->table('employee_assignments')->select('id', 'employee_id')->get()->keyBy('id');
$localAssignments = $localDb->table('employee_assignments')->select('id', 'employee_id')->get()->groupBy('employee_id');

$countInserted = 0;
$countUpdated = 0;
$countFailed = 0;

foreach ($remoteComponents as $comp) {
    // Map employee ID
    $remEmp = $remoteEmployees->get($comp->employee_id);
    if (!$remEmp) {
        echo "Remote employee not found for ID: " . $comp->employee_id . "\n";
        $countFailed++;
        continue;
    }
    
    $locEmp = $localEmployees->get($remEmp->employee_id);
    if (!$locEmp) {
        echo "Local employee not found for NIK: " . $remEmp->employee_id . "\n";
        $countFailed++;
        continue;
    }
    
    $localEmployeeId = $locEmp->id;
    
    // Map assignment ID
    $localAssignmentId = null;
    if ($comp->employee_assignment_id) {
        $remAss = $remoteAssignments->get($comp->employee_assignment_id);
        if ($remAss) {
            // Find active assignment for this employee locally
            $locAssList = $localAssignments->get($localEmployeeId);
            if ($locAssList && count($locAssList) > 0) {
                // Just pick the first or active one. For simplicity, pick first.
                $localAssignmentId = $locAssList[0]->id;
            }
        }
    }
    
    // Assume payroll_component_id is the same. Let's check if it exists
    $componentExists = $localDb->table('payroll_components')->where('id', $comp->payroll_component_id)->exists();
    if (!$componentExists) {
        echo "Local payroll component missing for ID: " . $comp->payroll_component_id . "\n";
        $countFailed++;
        continue;
    }

    $existing = $localDb->table('employee_salary_components')
        ->where('employee_id', $localEmployeeId)
        ->where('payroll_component_id', $comp->payroll_component_id)
        ->first();

    if ($existing) {
        $localDb->table('employee_salary_components')
            ->where('id', $existing->id)
            ->update([
                'employee_assignment_id' => $localAssignmentId,
                'calculation_type' => $comp->calculation_type,
                'amount' => $comp->amount,
                'percentage' => $comp->percentage,
                'formula' => $comp->formula,
                'effective_from' => $comp->effective_from,
                'is_active' => $comp->is_active,
                'created_at' => $comp->created_at,
                'updated_at' => $comp->updated_at,
            ]);
        $countUpdated++;
    } else {
        $localDb->table('employee_salary_components')
            ->insert([
                'employee_id' => $localEmployeeId,
                'employee_assignment_id' => $localAssignmentId,
                'payroll_component_id' => $comp->payroll_component_id,
                'calculation_type' => $comp->calculation_type,
                'amount' => $comp->amount,
                'percentage' => $comp->percentage,
                'formula' => $comp->formula,
                'effective_from' => $comp->effective_from,
                'is_active' => $comp->is_active,
                'created_at' => $comp->created_at,
                'updated_at' => $comp->updated_at,
            ]);
        $countInserted++;
    }
}

echo "Done. Inserted: $countInserted, Updated: $countUpdated, Failed: $countFailed\n";

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

$remoteAssignments = $remoteDb->table('employee_assignments')->get();

$remoteEmployees = $remoteDb->table('employees')->select('id', 'employee_id')->get()->keyBy('id');
$localEmployees = $localDb->table('employees')->select('id', 'employee_id')->get()->keyBy('employee_id');

$countUpdated = 0;
$countFailed = 0;

foreach ($remoteAssignments as $remAss) {
    // Map employee ID
    $remEmp = $remoteEmployees->get($remAss->employee_id);
    if (!$remEmp) {
        continue;
    }
    
    $locEmp = $localEmployees->get($remEmp->employee_id);
    if (!$locEmp) {
        continue;
    }
    
    $localEmployeeId = $locEmp->id;
    
    // Update local assignment
    // We assume the local assignment exists and we just update its salary_cycle_type (and employment_status just in case)
    $updated = $localDb->table('employee_assignments')
        ->where('employee_id', $localEmployeeId)
        ->update([
            'salary_cycle_type' => $remAss->salary_cycle_type,
            'employment_status' => $remAss->employment_status,
        ]);
        
    if ($updated) {
        $countUpdated++;
    } else {
        $countFailed++;
    }
}

echo "Done updating salary_cycle_type. Updated: $countUpdated\n";

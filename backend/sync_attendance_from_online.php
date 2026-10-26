<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

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

echo "=== START SYNC ATTENDANCE FROM HRMS_ONLINE (2026-10-01 ONWARDS) ===\n";

// Load remote and local employees
$remoteEmployees = $remoteDb->table('employees')->select('id', 'employee_id', 'name')->get()->keyBy('id');
$localEmployees = $localDb->table('employees')->select('id', 'employee_id', 'name')->get()->keyBy('employee_id');

// Load local assignments (active or primary per employee)
$localAssignments = $localDb->table('employee_assignments')
    ->select('id', 'employee_id')
    ->orderBy('is_primary', 'desc')
    ->get()
    ->groupBy('employee_id');

// Load remote shifts and local shifts mapping by code or id
$remoteShifts = $remoteDb->table('shifts')->get()->keyBy('id');
$localShiftsByCode = $localDb->table('shifts')->get()->keyBy('code');
$localShiftsById = $localDb->table('shifts')->get()->keyBy('id');

$remoteAttendances = $remoteDb->table('attendance')
    ->where('date', '>=', '2026-10-01')
    ->get();

echo "Found " . count($remoteAttendances) . " attendance records on remote DB from 2026-10-01.\n";

$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($remoteAttendances as $remAtt) {
    // Map employee
    $remEmp = $remoteEmployees->get($remAtt->employee_id);
    if (!$remEmp) {
        echo "Remote employee not found for ID: {$remAtt->employee_id}\n";
        $skipped++;
        continue;
    }

    $locEmp = $localEmployees->get($remEmp->employee_id);
    if (!$locEmp) {
        echo "Local employee not found for NIK: {$remEmp->employee_id} ({$remEmp->name})\n";
        $skipped++;
        continue;
    }

    $localEmployeeId = $locEmp->id;

    // Map assignment
    $localAssignmentId = null;
    $assList = $localAssignments->get($localEmployeeId);
    if ($assList && count($assList) > 0) {
        $localAssignmentId = $assList[0]->id;
    }

    // Map shift
    $localShiftId = null;
    if ($remAtt->shift_id) {
        $remShift = $remoteShifts->get($remAtt->shift_id);
        if ($remShift && isset($localShiftsByCode[$remShift->code])) {
            $localShiftId = $localShiftsByCode[$remShift->code]->id;
        } elseif (isset($localShiftsById[$remAtt->shift_id])) {
            $localShiftId = $localShiftsById[$remAtt->shift_id]->id;
        }
    }

    $dataToSave = [
        'employee_id' => $localEmployeeId,
        'employee_assignment_id' => $localAssignmentId,
        'shift_id' => $localShiftId,
        'date' => $remAtt->date,
        'clock_in_at' => $remAtt->clock_in_at,
        'clock_out_at' => $remAtt->clock_out_at,
        'break_start_at' => $remAtt->break_start_at ?? null,
        'break_end_at' => $remAtt->break_end_at ?? null,
        'break_minutes' => $remAtt->break_minutes ?? 0,
        'working_hours' => $remAtt->working_hours ?? 0,
        'clock_in_lat' => $remAtt->clock_in_lat ?? null,
        'clock_in_lng' => $remAtt->clock_in_lng ?? null,
        'clock_in_accuracy' => $remAtt->clock_in_accuracy ?? null,
        'clock_in_photo' => $remAtt->clock_in_photo ?? null,
        'clock_out_lat' => $remAtt->clock_out_lat ?? null,
        'clock_out_lng' => $remAtt->clock_out_lng ?? null,
        'clock_out_photo' => $remAtt->clock_out_photo ?? null,
        'overtime_clock_in_at' => $remAtt->overtime_clock_in_at ?? null,
        'overtime_clock_out_at' => $remAtt->overtime_clock_out_at ?? null,
        'late_minutes' => $remAtt->late_minutes ?? 0,
        'overtime_minutes' => $remAtt->overtime_minutes ?? 0,
        'status' => $remAtt->status ?? 'present',
        'is_face_verified' => $remAtt->is_face_verified ?? 0,
        'updated_at' => now(),
    ];

    $existing = $localDb->table('attendance')
        ->where('employee_id', $localEmployeeId)
        ->where('date', $remAtt->date)
        ->first();

    if ($existing) {
        $localDb->table('attendance')
            ->where('id', $existing->id)
            ->update($dataToSave);
        $updated++;
    } else {
        $dataToSave['created_at'] = $remAtt->created_at ?? now();
        $localDb->table('attendance')->insert($dataToSave);
        $inserted++;
    }
}

echo "=== SYNC COMPLETE ===\n";
echo "Inserted: $inserted | Updated: $updated | Skipped: $skipped\n";

<?php
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$csvFile = 'd:\Project\HRMS\absensi.csv';
$file = fopen($csvFile, 'r');

// Skip first 2 lines
fgetcsv($file, 0, ';');
fgetcsv($file, 0, ';');
// Read header (line 3)
$header = fgetcsv($file, 0, ';');

$defaultShift = DB::table('shifts')->where('code', 'NORMAL')->first();
if (!$defaultShift) {
    echo "Default shift NORMAL not found.\n";
    exit;
}

$count = 0;
$batchSize = 500;
DB::beginTransaction();
try {
    while (($data = fgetcsv($file, 0, ';')) !== false) {
        if (count($data) < 7) continue;

        $date = $data[2]; 
        $nip = trim($data[5]); 
        $statusCsv = trim($data[6]); 

        if (strtoupper($statusCsv) !== 'HADIR') {
            continue; 
        }

        $employee = DB::table('employees')->where('employee_id', $nip)->first();
        if (!$employee) {
            // echo "Employee not found for NIP: $nip\n";
            continue;
        }

        $assignment = DB::table('employee_assignments')
                        ->where('employee_id', $employee->id)
                        ->orderBy('is_primary', 'desc')
                        ->first();
        
        $assignmentId = $assignment ? $assignment->id : null;

        $employeeShift = DB::table('employee_shifts')
                            ->where('employee_id', $employee->id)
                            ->where('effective_date', $date)
                            ->first();

        $shift = $defaultShift;
        $shiftId = $defaultShift->id;
        if ($employeeShift && $employeeShift->shift_id) {
            $s = DB::table('shifts')->where('id', $employeeShift->shift_id)->first();
            if ($s) {
                $shift = $s;
                $shiftId = $s->id;
            }
        }

        $clockIn = $date . ' ' . $shift->clock_in;
        $clockOut = $date . ' ' . $shift->clock_out;

        $exists = DB::table('attendance')
                    ->where('employee_id', $employee->id)
                    ->where('date', $date)
                    ->exists();

        if (!$exists) {
            DB::table('attendance')->insert([
                'employee_id' => $employee->id,
                'employee_assignment_id' => $assignmentId,
                'shift_id' => $shiftId,
                'date' => $date,
                'clock_in_at' => $clockIn,
                'clock_out_at' => $clockOut,
                'working_hours' => $shift->working_hours ?? 8,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => 'present',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;

            if ($count % $batchSize === 0) {
                DB::commit();
                DB::beginTransaction();
            }
        }
    }
    DB::commit();
    echo "Import success! $count records inserted.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
fclose($file);

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\Attendance;
use App\Models\OvertimeRequest;
use Carbon\Carbon;

class GenerateDummyDataCommand extends Command
{
    protected $signature = 'app:generate-dummy-data';
    protected $description = 'Generate massive dummy data for attendance and shifts';

    public function handle()
    {
        $this->info('Starting Dummy Data Generation...');

        $startDate = Carbon::create(2026, 7, 1);
        $endDate = Carbon::create(2026, 9, 22);
        
        $employees = Employee::all();
        $defaultShift = Shift::where('is_active', true)->first();

        if (!$defaultShift) {
            $this->error('No active shift found. Please create a shift first.');
            return;
        }

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $bar = $this->output->createProgressBar($employees->count() * $totalDays);

        foreach ($employees as $emp) {
            // First, make sure they have a shift assignment
            \App\Models\EmployeeShift::updateOrCreate(
                ['employee_id' => $emp->id],
                [
                    'shift_id' => $defaultShift->id,
                    'effective_date' => '2026-01-01',
                    'is_active' => true
                ]
            );

            $currentDate = $startDate->copy();
            
            // Delete old data in this range to avoid duplicates
            Attendance::where('employee_id', $emp->id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->delete();

            while ($currentDate->lte($endDate)) {
                $isWeekend = $currentDate->isWeekend();
                $dateStr = $currentDate->format('Y-m-d');
                
                $attendance = new Attendance();
                $attendance->employee_id = $emp->id;
                $attendance->shift_id = $defaultShift->id;
                $attendance->date = $dateStr;
                $attendance->working_hours = $defaultShift->working_hours ?? 8;
                $attendance->break_minutes = $defaultShift->break_minutes ?? 60;
                
                if ($isWeekend) {
                    $attendance->status = 'holiday';
                } else {
                    $rand = rand(1, 100);
                    if ($rand <= 5) {
                        // Leave
                        $attendance->status = 'leave';
                    } elseif ($rand <= 20) {
                        // Late
                        $attendance->status = 'late';
                        $lateMins = rand(10, 50);
                        $clockIn = $currentDate->copy()->setTime(8, 0)->addMinutes($lateMins);
                        $clockOut = $currentDate->copy()->setTime(17, rand(0, 30));
                        
                        $attendance->clock_in_at = $clockIn;
                        $attendance->clock_out_at = $clockOut;
                        $attendance->late_minutes = $lateMins;
                    } elseif ($rand <= 30) {
                        // Overtime
                        $attendance->status = 'present';
                        $clockIn = $currentDate->copy()->setTime(7, rand(30, 55));
                        $overtimeHours = rand(1, 3);
                        $clockOut = $currentDate->copy()->setTime(17 + $overtimeHours, rand(0, 30));
                        
                        $attendance->clock_in_at = $clockIn;
                        $attendance->clock_out_at = $clockOut;
                        $attendance->overtime_minutes = $overtimeHours * 60;
                        $attendance->overtime_clock_out_at = $clockOut;
                        
                        // Create OvertimeRequest
                        OvertimeRequest::updateOrCreate([
                            'employee_id' => $emp->id,
                            'date' => $dateStr
                        ], [
                            'shift_id' => $defaultShift->id,
                            'requested_start_time' => '17:00:00',
                            'requested_end_time' => (17 + $overtimeHours).':00:00',
                            'reason' => 'Kerja Lembur Target',
                            'status' => 'approved'
                        ]);
                    } else {
                        // Normal Present
                        $attendance->status = 'present';
                        $clockIn = $currentDate->copy()->setTime(7, rand(30, 55));
                        $clockOut = $currentDate->copy()->setTime(17, rand(0, 30));
                        
                        $attendance->clock_in_at = $clockIn;
                        $attendance->clock_out_at = $clockOut;
                        $attendance->late_minutes = 0;
                        $attendance->overtime_minutes = 0;
                    }
                    
                    // Dummy Lat/Lng for presence
                    if (in_array($attendance->status, ['present', 'late'])) {
                        $attendance->clock_in_lat = '-6.200000';
                        $attendance->clock_in_lng = '106.816666';
                        $attendance->clock_out_lat = '-6.200000';
                        $attendance->clock_out_lng = '106.816666';
                        $attendance->is_face_verified = true;
                    }
                }
                
                $attendance->save();
                
                $currentDate->addDay();
                $bar->advance();
            }
        }

        $bar->finish();
        $this->info("\nDummy Data Generation Complete!");
    }
}

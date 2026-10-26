<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\AttendanceLog;

class AutoClockOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-clockout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically clock out employees whose shift has ended';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $this->info("Running auto clock-out at {$now->toDateTimeString()}");

        // Get attendances that have clocked in but not clocked out
        $attendances = Attendance::with('shift')
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->get();

        $count = 0;
        foreach ($attendances as $attendance) {
            $shift = $attendance->shift;
            $clockInAt = Carbon::parse($attendance->clock_in_at);

            if (!$shift || !$shift->clock_out) {
                // Jika tidak ada shift yang terasosiasi, auto clock out jika sudah lewat 14 jam dari clock_in
                if (abs($now->diffInHours($clockInAt)) >= 14) {
                    $autoClockOutTime = $clockInAt->copy()->addHours(8);
                    $attendance->clock_out_at = $autoClockOutTime;
                    $attendance->working_hours = 8;
                    $attendance->save();

                    AttendanceLog::create([
                        'employee_id' => $attendance->employee_id,
                        'attendance_id' => $attendance->id,
                        'action' => 'clock_out',
                        'action_at' => $autoClockOutTime,
                        'device_info' => 'Auto clock out by system (no shift)',
                    ]);
                    $count++;
                }
                continue;
            }

            $date = Carbon::parse($attendance->date);
            
            // Expected clock out time
            $expectedClockOutStr = $date->format('Y-m-d') . ' ' . $shift->clock_out;
            $expectedClockOut = Carbon::parse($expectedClockOutStr);

            $isOvernight = $shift->cross_midnight || ($shift->clock_in && $shift->clock_out && $shift->clock_in > $shift->clock_out);
            if ($isOvernight) {
                $expectedClockOut->addDay();
            }

            // Buffer: Beri toleransi waktu 4 jam setelah jam selesai shift sebelum auto clock out
            // agar karyawan yang bekerja shift overlap/malam (cth: 19:00 - 02:00) masih dapat menekan tombol clock out
            $autoClockOutThreshold = $expectedClockOut->copy()->addHours(4);

            // If the current time is past the auto clock out threshold, auto clock out
            if ($now->greaterThanOrEqualTo($autoClockOutThreshold)) {
                $attendance->clock_out_at = $expectedClockOut;
                
                // Calculate working hours if needed
                if ($attendance->clock_in_at && $shift->working_hours) {
                    $attendance->working_hours = $shift->working_hours;
                } else {
                    $effectiveMinutes = max($clockInAt->diffInMinutes($expectedClockOut) - (int) $attendance->break_minutes, 0);
                    $attendance->working_hours = round($effectiveMinutes / 60, 2);
                }
                
                $attendance->save();
                
                // Add a log
                AttendanceLog::create([
                    'employee_id' => $attendance->employee_id,
                    'attendance_id' => $attendance->id,
                    'action' => 'clock_out',
                    'action_at' => $expectedClockOut,
                    'device_info' => 'Auto clock out by system',
                ]);
                $count++;
            }
        }

        $this->info("Successfully auto clocked out {$count} employees.");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('overtime:auto-clockout')]
#[Description('Automatically clock out overtime 5 minutes before the next shift starts')]
class AutoClockOutOvertime extends Command
{
    public function handle()
    {
        $now = now();
        $ongoingOvertimes = \App\Models\OvertimeRequest::where('status', 'in_progress')->get();

        foreach ($ongoingOvertimes as $overtime) {
            $employee = \App\Models\Employee::find($overtime->employee_id);
            if (!$employee) continue;

            $nextShift = $employee->activeShift($now->format('Y-m-d'));
            if (!$nextShift) continue;

            $shiftStartTime = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $nextShift->clock_in);
            
            if ($now->greaterThanOrEqualTo($shiftStartTime->copy()->subMinutes(5))) {
                $attendance = \App\Models\Attendance::where('employee_id', $overtime->employee_id)
                    ->whereNotNull('overtime_clock_in_at')
                    ->whereNull('overtime_clock_out_at')
                    ->first();

                if ($attendance) {
                    $clockInTime = \Carbon\Carbon::parse($attendance->overtime_clock_in_at);
                    $overtimeMinutes = $now->diffInMinutes($clockInTime);
                    
                    $attendance->update([
                        'overtime_clock_out_at' => $now,
                        'overtime_minutes' => $overtimeMinutes,
                        'overtime_report_text' => 'Auto closed by system (next shift is starting)',
                    ]);
                }

                $overtime->update([
                    'status' => 'done',
                    'actual_end_time' => $now,
                    'result_note' => 'Auto closed by system (next shift is starting)',
                ]);

                $this->info("Auto clocked out overtime ID {$overtime->id}");
            }
        }
    }
}

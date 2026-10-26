<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\PermissionRequest;
use App\Models\Employee;
use Carbon\Carbon;

#[Signature('permit:check-expired')]
#[Description('Check expired business permits and reset free presence status')]
class CheckExpiredBusinessPermits extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $time = $now->format('H:i:s');
        
        $expiredPermits = PermissionRequest::where('type', 'business')
            ->where('status', 'approved')
            ->where(function($query) use ($today, $time) {
                $query->whereRaw('COALESCE(end_date, date) < ?', [$today])
                      ->orWhere(function($q) use ($today, $time) {
                          $q->whereRaw('COALESCE(end_date, date) = ?', [$today])
                            ->where('end_time', '<', $time);
                      });
            })
            ->get();

        $count = 0;
        foreach ($expiredPermits as $permit) {
            $employee = Employee::find($permit->employee_id);
            if ($employee && $employee->can_clock_in_anywhere && $employee->attendance_location_type !== 'anywhere') {
                // Ensure no other active business permit
                $hasOtherActivePermit = PermissionRequest::where('employee_id', $employee->id)
                    ->where('type', 'business')
                    ->where('status', 'approved')
                    ->where('id', '!=', $permit->id)
                    ->where(function($query) use ($today, $time) {
                        $query->whereRaw('COALESCE(end_date, date) > ?', [$today])
                              ->orWhere(function($q) use ($today, $time) {
                                  $q->whereRaw('COALESCE(end_date, date) = ?', [$today])
                                    ->where('end_time', '>=', $time);
                              });
                    })->exists();

                if (!$hasOtherActivePermit) {
                    $employee->update(['can_clock_in_anywhere' => false]);
                    $count++;
                }
            }
        }

        $this->info("Reset free presence for {$count} employees.");
    }
}

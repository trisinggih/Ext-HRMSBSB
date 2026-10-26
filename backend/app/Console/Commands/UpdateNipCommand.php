<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Services\NipGeneratorService;

class UpdateNipCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:nip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all existing employee NIPs according to the system standard format';

    /**
     * Execute the console command.
     */
    public function handle(NipGeneratorService $nipService)
    {
        $this->info('Starting NIP updates...');

        // Fetch all employees, ordered by created_at or id to preserve sequence if needed
        $employees = Employee::orderBy('id', 'asc')->get();
        $count = 0;

        foreach ($employees as $employee) {
            $joinDate = $employee->join_date ? \Carbon\Carbon::parse($employee->join_date) : ($employee->created_at ?? now());
            
            $newNip = $nipService->generateNip($joinDate);
            
            $oldNip = $employee->employee_id;
            $employee->employee_id = $newNip;
            $employee->save();

            $this->line("Updated Employee ID {$employee->id}: {$oldNip} -> {$newNip}");
            $count++;
        }

        $this->info("Completed. Updated {$count} employees.");
    }
}

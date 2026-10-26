<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessRecurringIncentives extends Command
{
    protected $signature = 'hrms:process-incentives {--date= : The date to process (YYYY-MM-DD)}';

    protected $description = 'Process recurring incentives and create bonuses/slips if applicable.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateStr = $this->option('date') ?: now()->toDateString();
        $date = \Carbon\Carbon::parse($dateStr);
        $dayOfMonth = $date->day;
        
        $this->info("Processing recurring incentives for date: {$dateStr} (Day {$dayOfMonth})");

        // 1. Process incentives with payout_day_of_month == today
        $this->processSeparateSlips($date);

        // 2. Process incentives with payout_day_of_month == null (Run on the 1st of the month)
        if ($dayOfMonth === 1) {
            $this->processRegularIncentives($date);
        }
        
        $this->info("Done.");
    }

    private function processSeparateSlips(\Carbon\Carbon $date)
    {
        $incentives = \App\Models\EmployeeRecurringIncentive::where('is_active', true)
            ->where('payout_day_of_month', $date->day)
            ->get();
            
        if ($incentives->isEmpty()) {
            return;
        }
        
        $this->info("Found {$incentives->count()} separate slip incentives to process.");
        
        // Ensure there is a PayrollPeriod for this bonus date
        $periodName = "Bonus " . $date->translatedFormat('d F Y');
        $period = \App\Models\PayrollPeriod::firstOrCreate(
            [
                'payment_date' => $date->toDateString(),
                'type' => 'bonus',
            ],
            [
                'name' => $periodName,
                'period' => $date->format('Y-m'),
                'start_date' => $date->copy()->startOfMonth()->toDateString(),
                'end_date' => $date->copy()->endOfMonth()->toDateString(),
                'status' => 'draft',
            ]
        );
        
        $calculator = app(\App\Services\PayrollCalculatorService::class);
        
        foreach ($incentives as $incentive) {
            // Check if already processed
            $exists = \App\Models\Reward::where('employee_id', $incentive->employee_id)
                ->where('title', $incentive->title)
                ->whereMonth('reward_date', $date->month)
                ->whereYear('reward_date', $date->year)
                ->exists();
                
            if ($exists) continue;
            
            // Map incentive type to reward valid enum
            $rewardType = in_array($incentive->type, ['employee_of_month', 'performance', 'bonus', 'appreciation', 'other']) 
                ? $incentive->type 
                : 'other';

            // Create Reward
            $reward = \App\Models\Reward::create([
                'employee_id' => $incentive->employee_id,
                'type' => $rewardType,
                'title' => $incentive->title,
                'description' => 'Auto-generated from Recurring Incentive',
                'amount' => $incentive->amount,
                'reward_date' => $date->toDateString(),
            ]);
            
            $this->info("Created reward for Employee {$incentive->employee_id}: {$incentive->title}");
            
            // Generate PayrollRun (Bonus Slip)
            $employee = \App\Models\Employee::find($incentive->employee_id);
            $calc = $calculator->calculateForEmployee($employee, $period, true, true); // true = $assumeFullAttendance, true = $isBonusOnly
            
            $details = $calc['_details'];
            unset($calc['_details']);

            $run = \App\Models\PayrollRun::updateOrCreate(
                ['payroll_period_id' => $period->id, 'employee_id' => $employee->id],
                $calc
            );

            // save details
            $run->details()->delete();
            foreach ($details as $d) {
                \App\Models\PayrollDetail::create([
                    'payroll_run_id' => $run->id,
                    'payroll_component_id' => $d['payroll_component_id'],
                    'label' => $d['label'],
                    'type' => $d['type'],
                    'amount' => $d['amount'],
                ]);
            }
        }
    }

    private function processRegularIncentives(\Carbon\Carbon $date)
    {
        $incentives = \App\Models\EmployeeRecurringIncentive::where('is_active', true)
            ->whereNull('payout_day_of_month')
            ->get();
            
        if ($incentives->isEmpty()) {
            return;
        }
        
        $this->info("Found {$incentives->count()} regular incentives to process.");
        
        foreach ($incentives as $incentive) {
            // Check if already processed
            $exists = \App\Models\Reward::where('employee_id', $incentive->employee_id)
                ->where('title', $incentive->title)
                ->whereMonth('reward_date', $date->month)
                ->whereYear('reward_date', $date->year)
                ->exists();
                
            if ($exists) continue;
            
            // Map incentive type to reward valid enum
            $rewardType = in_array($incentive->type, ['employee_of_month', 'performance', 'bonus', 'appreciation', 'other']) 
                ? $incentive->type 
                : 'other';

            // Create Reward
            \App\Models\Reward::create([
                'employee_id' => $incentive->employee_id,
                'type' => $rewardType,
                'title' => $incentive->title,
                'description' => 'Auto-generated from Recurring Incentive',
                'amount' => $incentive->amount,
                'reward_date' => $date->toDateString(),
            ]);
            
            $this->info("Created regular reward for Employee {$incentive->employee_id}: {$incentive->title}");
        }
    }
}

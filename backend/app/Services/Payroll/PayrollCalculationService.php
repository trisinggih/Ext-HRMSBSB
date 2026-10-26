<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollDetail;
use App\Models\PayrollRule;
use App\Models\PayrollComponent;
use App\Models\PayrollInput;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    protected ExpressionEvaluator $evaluator;

    public function __construct(ExpressionEvaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }

    public function calculate(PayrollPeriod $period, Employee $employee): ?PayrollRun
    {
        // Check if employee has an active salary structure
        $activeStructure = $employee->salaryStructures()
            ->where('effective_date', '<=', $period->end_date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$activeStructure) {
            \Log::warning("No active salary structure found for employee {$employee->id}");
            return null;
        }

        // Gather all variables for rules (Attendance, Sales, Overtime, etc.)
        $variables = $this->gatherVariables($period, $employee);

        $components = $activeStructure->structure->components()->with('component.rules.details')->get();
        $payrollInputs = PayrollInput::where('payroll_period_id', $period->id)
                                      ->where('employee_id', $employee->id)
                                      ->get()
                                      ->keyBy('payroll_component_id');

        $details = [];
        $grossEarnings = 0;
        $totalBenefit = 0;
        $totalDeduction = 0;

        foreach ($components as $structComponent) {
            $component = $structComponent->component;
            
            // Allow override via PayrollInput
            $input = $payrollInputs->get($component->id);

            $result = $this->processComponent($component, $structComponent->amount, $variables, $input, $period);

            if ($result) {
                $details[] = $result;

                if ($component->type === 'earning') {
                    $grossEarnings += $result['amount'];
                } elseif ($component->type === 'deduction') {
                    $totalDeduction += $result['amount'];
                } elseif ($component->type === 'benefit') {
                    $totalBenefit += $result['amount'];
                }
            }
        }

        $netSalary = $grossEarnings - $totalDeduction;

        DB::beginTransaction();
        try {
            // Save PayrollRun
            $payrollRun = PayrollRun::updateOrCreate(
                ['payroll_period_id' => $period->id, 'employee_id' => $employee->id],
                [
                    'gross_earnings' => $grossEarnings,
                    'total_benefit' => $totalBenefit,
                    'gross_salary' => $grossEarnings, // legacy compat
                    'total_deduction' => $totalDeduction,
                    'net_salary' => $netSalary,
                    'status' => 'calculated',
                    'calculated_at' => now(),
                    'breakdown' => [], // Can be populated with summary
                ]
            );

            // Save PayrollDetails
            PayrollDetail::where('payroll_run_id', $payrollRun->id)->delete();
            foreach ($details as $detail) {
                $payrollRun->details()->create($detail);
            }

            DB::commit();
            return $payrollRun;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to save payroll run: " . $e->getMessage());
            throw $e;
        }
    }

    protected function gatherVariables(PayrollPeriod $period, Employee $employee): array
    {
        // 1. Attendance
        $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->get();

        $presentDays = $attendances->whereIn('status', ['present', 'late', 'early_leave'])->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('late_minutes', '>', 0)->count();
        
        $attendanceData = [
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_count' => $lateCount,
        ];

        // 2. Overtime (from attendance overtime_minutes)
        $overtimeMinutes = $attendances->sum('overtime_minutes');
        $overtimeData = [
            'approved_hours' => round($overtimeMinutes / 60, 2),
        ];

        // 3. Loan
        $loans = \App\Models\EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->get();

        $loanData = [
            'installment_amount' => $loans->sum('installment_amount'),
            'total_remaining' => $loans->sum('remaining_balance'),
        ];

        // 4. Sales (Placeholder, to be integrated with Sales Module)
        $salesData = [
            'total' => 0,
        ];

        return [
            'employee' => $employee->toArray(),
            'attendance' => $attendanceData,
            'overtime' => $overtimeData,
            'sales' => $salesData,
            'loan' => $loanData,
            'period' => $period->toArray(),
        ];
    }

    protected function processComponent(PayrollComponent $component, ?float $structureAmount, array $variables, ?PayrollInput $input, PayrollPeriod $period): ?array
    {
        // Find applicable rule
        $rule = $component->rules()
            ->where('is_active', true)
            ->where('effective_from', '<=', $period->end_date)
            ->where(function ($q) use ($period) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $period->end_date);
            })
            ->orderBy('priority', 'asc')
            ->get()
            ->first(function ($r) use ($variables) {
                return $this->evaluator->evaluateCondition($r->condition, $variables);
            });

        $amount = 0;
        $quantity = 1;
        $rate = 0;
        $baseAmount = $structureAmount ?? $component->default_value ?? 0;

        $usedRuleId = null;
        $snapshot = [];

        // Manual Input Overrides everything if its source is MANUAL
        if ($input) {
            $amount = $input->amount ?? ($input->quantity * $baseAmount);
            $quantity = $input->quantity ?? 1;
            $snapshot['manual_override'] = true;
            $snapshot['input_note'] = $input->note;
        } elseif ($rule) {
            $usedRuleId = $rule->id;
            $method = $rule->method;

            switch ($method) {
                case 'FIXED_AMOUNT':
                    $amount = $rule->formula ? $this->evaluator->evaluate($rule->formula, $variables) : $baseAmount;
                    break;
                case 'PERCENTAGE':
                    $rate = $rule->formula ? $this->evaluator->evaluate($rule->formula, $variables) : 0;
                    $amount = $baseAmount * ($rate / 100);
                    break;
                case 'PER_DAY':
                    $quantity = $variables['attendance']['present_days'] ?? 0;
                    $rate = $rule->formula ? $this->evaluator->evaluate($rule->formula, $variables) : $baseAmount;
                    $amount = $quantity * $rate;
                    break;
                case 'PER_HOUR':
                    $quantity = $variables['overtime']['approved_hours'] ?? 0;
                    $rate = $rule->formula ? $this->evaluator->evaluate($rule->formula, $variables) : $baseAmount;
                    $amount = $quantity * $rate;
                    break;
                case 'PER_OCCURRENCE':
                    $quantity = $variables['attendance']['late_count'] ?? 0;
                    $rate = $rule->formula ? $this->evaluator->evaluate($rule->formula, $variables) : $baseAmount;
                    $amount = $quantity * $rate;
                    break;
                case 'TIERED':
                    $baseForTier = $variables['sales']['total'] ?? 0; // Example
                    $amount = $this->calculateTiered($rule, $baseForTier, $variables);
                    break;
                case 'FORMULA':
                    $amount = $this->evaluator->evaluate($rule->formula, $variables);
                    break;
                case 'SCHEDULED_INSTALLMENT':
                    $amount = $variables['loan']['installment_amount'] ?? 0;
                    break;
                // TAX_ENGINE handled later
                default:
                    $amount = $baseAmount;
            }

            $snapshot['rule'] = $rule->toArray();
            $snapshot['variables'] = $variables;
        } else {
            // No rule matched, fallback to calculation_method on component directly
            if ($component->calculation_source === 'FIXED') {
                $amount = $baseAmount;
            }
        }

        if ($amount == 0) return null; // Don't store 0 amounts

        return [
            'payroll_component_id' => $component->id,
            'payroll_rule_id' => $usedRuleId,
            'label' => $component->name,
            'type' => $component->type,
            'quantity' => $quantity,
            'rate' => $rate,
            'base_amount' => $baseAmount,
            'amount' => $amount,
            'source' => $rule->source ?? $component->calculation_source ?? 'FIXED',
            'calculation_snapshot' => $snapshot,
        ];
    }

    protected function calculateTiered(PayrollRule $rule, float $baseValue, array $variables): float
    {
        $amount = 0;
        $details = $rule->details()->orderBy('priority', 'asc')->get();

        foreach ($details as $detail) {
            if ($baseValue >= ($detail->min_value ?? 0) && ($detail->max_value === null || $baseValue <= $detail->max_value)) {
                if ($detail->fixed_amount) {
                    $amount = $detail->fixed_amount;
                } elseif ($detail->rate) {
                    $amount = $baseValue * ($detail->rate / 100);
                } elseif ($detail->formula) {
                    $amount = $this->evaluator->evaluate($detail->formula, array_merge($variables, ['tier_base' => $baseValue]));
                }
                break; // Rule matched, stop processing tiers
            }
        }
        return $amount;
    }
}

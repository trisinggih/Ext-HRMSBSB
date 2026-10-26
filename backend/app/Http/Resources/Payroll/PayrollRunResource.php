<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $emp = $this->employee;
        $breakdown = $this->breakdown ?? [];
        $att = $breakdown['attendance'] ?? [];
        $vars = $breakdown['variables'] ?? [];

        $companyId = $this->assignment?->company_id 
            ?? $this->period?->company_id 
            ?? $this->assignment?->placement_company_id;
        $comp = null;
        if ($companyId) {
            $comp = \App\Models\Company::find($companyId);
        }
        if (!$comp) {
            $comp = $this->assignment?->company ?? $emp?->company;
        }

        $branch = $this->assignment?->branch 
            ?? $this->period?->branch 
            ?? $emp?->branch;

        $branchData = $branch ? [
            'id'      => $branch->id,
            'name'    => $branch->name,
            'address' => $branch->address,
            'phone'   => $branch->phone,
            'email'   => $branch->email,
        ] : null;

        $companyData = $comp ? [
            'id'          => $comp->id,
            'name'        => $comp->name,
            'address'     => $comp->address,
            'phone'       => $comp->phone,
            'email'       => $comp->email,
            'logo'        => $comp->effective_logo_url,
            'branch'      => $branchData,
            'branch_name' => $branch?->name,
        ] : null;

        return [
            'id'                 => $this->id,
            'company'            => $companyData,
            'payroll_period_id'  => $this->payroll_period_id,
            'employee_id'        => $this->employee_id,
            'gross_salary'       => $this->gross_salary,
            'total_allowance'    => $this->total_allowance,
            'total_overtime'     => $this->total_overtime,
            'total_bonus'        => $this->total_bonus,
            'total_commission'   => $this->total_commission,
            'total_reward'       => $this->total_reward,
            'total_deduction'    => $this->total_deduction,
            'total_tax'          => $this->total_tax,
            'total_bpjs'         => $this->total_bpjs,
            'net_salary'         => $this->net_salary,
            'status'             => $this->status,
            'breakdown'          => $this->breakdown,
            'attendance' => [
                'total_period_days' => $att['total_period_days'] ?? $vars['total_period_days'] ?? $vars['total_days_in_month'] ?? 0,
                'work_days'         => $att['work_days']         ?? $vars['total_working_days'] ?? 0,
                'hadir'             => $att['hadir']             ?? $vars['total_attendance_days'] ?? 0,
                'libur'             => $att['libur']             ?? $vars['total_day_off'] ?? 0,
                'cuti'              => $att['cuti']              ?? $vars['total_paid_leave_days'] ?? 0,
                'alpha'             => $att['alpha']             ?? $vars['total_unpaid_leave_days'] ?? 0,
                'rate_per_day'      => $att['rate_per_day']      ?? $vars['rate_per_day'] ?? 0,
                'rate_per_hour'     => $att['rate_per_hour']     ?? $vars['rate_per_hour'] ?? 0,
            ],
            'employee' => $emp ? [
                'id'                    => $emp->id,
                'name'                  => $emp->name,
                'employee_code'         => $this->assignment?->nip ?: $emp->employee_id,
                'phone'                 => $emp->phone,
                'join_date'             => $this->assignment?->join_date?->format('d/m/Y'),
                'employment_status'     => $this->assignment?->employment_status ?? '-',
                'bpjs_kesehatan'        => $emp->bpjs_kesehatan,
                'bpjs_ketenagakerjaan'  => $emp->bpjs_ketenagakerjaan,
                'npwp'                  => $emp->npwp,
                'company'               => $companyData,
                'branch'     => $branchData,
                'department' => $this->assignment && $this->assignment->department ? ['id' => $this->assignment->department->id, 'name' => $this->assignment->department->name] : null,
                'division'   => $this->assignment && $this->assignment->division   ? ['id' => $this->assignment->division->id,   'name' => $this->assignment->division->name]   : null,
                'position'   => $this->assignment && $this->assignment->position   ? ['id' => $this->assignment->position->id,   'name' => $this->assignment->position->name]   : null,
            ] : null,
            'period' => $this->period ? [
                'id'              => $this->period->id,
                'name'            => $this->period->name,
                'branch_id'       => $this->period->branch_id,
                'branch'          => $this->period->branch ? ['id' => $this->period->branch->id, 'name' => $this->period->branch->name] : null,
                'period'          => $this->period->period,
                'cycle_type'      => $this->period->cycle_type,
                'start_date'      => $this->period->start_date?->toDateString(),
                'end_date'        => $this->period->end_date?->toDateString(),
                'payment_date'    => $this->period->payment_date?->toDateString(),
                'formatted_range' => $this->period->start_date && $this->period->end_date
                    ? ($this->period->start_date->format('d/m/Y') . ' - ' . $this->period->end_date->format('d/m/Y'))
                    : '-',
            ] : null,
            'details'    => $this->whenLoaded('details'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

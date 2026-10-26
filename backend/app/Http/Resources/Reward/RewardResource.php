<?php

namespace App\Http\Resources\Reward;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'employee_id' => $this->employee->employee_id,
                'name' => $this->employee->name,
            ]),
            'payroll_period_id' => $this->payroll_period_id,
            'payroll_period' => $this->whenLoaded('payrollPeriod', fn () => [
                'id' => $this->payrollPeriod->id,
                'name' => $this->payrollPeriod->name,
                'period' => $this->payrollPeriod->period,
                'start_date' => $this->payrollPeriod->start_date?->toDateString(),
                'end_date' => $this->payrollPeriod->end_date?->toDateString(),
            ]),
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'reward_date' => $this->reward_date?->toDateString(),
            'attachment' => $this->attachment,
            'attachment_url' => $this->attachment ? asset('storage/' . $this->attachment) : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

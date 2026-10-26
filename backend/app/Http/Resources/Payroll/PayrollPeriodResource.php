<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company'),
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch'),
            'name' => $this->name,
            'cycle_type' => $this->cycle_type,
            'period' => $this->period,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'payment_date' => $this->payment_date?->toDateString(),
            'status' => $this->status,
            'processed_by' => $this->processed_by,
            'processed_at' => $this->processed_at?->toISOString(),
            'runs_count' => $this->whenNotNull($this->runs_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources\Termination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerminationResource extends JsonResource
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
                'employment_status' => $this->employee->employment_status,
                'is_active' => $this->employee->is_active,
            ]),
            'type' => $this->type,
            'termination_date' => $this->termination_date?->toDateString(),
            'reason' => $this->reason,
            'status' => $this->status,
            'clearances' => $this->whenLoaded('clearances'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

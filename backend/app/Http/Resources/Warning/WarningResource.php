<?php

namespace App\Http\Resources\Warning;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarningResource extends JsonResource
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
            'warning_type' => $this->warning_type,
            'issue_date' => $this->issue_date?->toDateString(),
            'reason' => $this->reason,
            'expired_date' => $this->expired_date?->toDateString(),
            'status' => $this->status,
            'evidence' => $this->evidence,
            'evidence_url' => $this->evidence ? asset('storage/' . $this->evidence) : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'leave_type_id' => $this->leave_type_id,
            'leave_type' => $this->whenLoaded('leaveType', fn () => [
                'id' => $this->leaveType->id,
                'name' => $this->leaveType->name,
                'code' => $this->leaveType->code,
                'category' => $this->leaveType->category,
            ]),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'total_days' => (float) $this->total_days,
            'reason' => $this->reason,
            'attachment' => $this->attachment,
            'status' => $this->status,
            'approval' => $this->whenLoaded('approval', fn () => $this->approval ? [
                'id' => $this->approval->id,
                'status' => $this->approval->status,
                'current_step' => $this->approval->current_step,
            ] : null),
            'approved_at' => $this->approved_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

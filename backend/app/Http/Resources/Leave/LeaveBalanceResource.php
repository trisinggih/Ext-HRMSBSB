<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveBalanceResource extends JsonResource
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
            'year' => $this->year,
            'quota' => (float) $this->quota,
            'used' => (float) $this->used,
            'carried_forward' => (float) $this->carried_forward,
            'expired' => (float) $this->expired,
            'remaining' => (float) $this->remaining,
            'available' => $this->available(),
        ];
    }
}

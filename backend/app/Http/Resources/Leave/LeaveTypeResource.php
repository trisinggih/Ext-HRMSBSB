<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'default_days' => $this->default_days,
            'is_paid' => $this->is_paid,
            'is_carry_forward' => $this->is_carry_forward,
            'max_carry_forward_days' => $this->max_carry_forward_days,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

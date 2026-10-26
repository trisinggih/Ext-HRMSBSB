<?php

namespace App\Http\Resources\Shift;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company_name' => $this->whenLoaded('company', fn () => $this->company->name),
            'name' => $this->name,
            'code' => $this->code,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'break_start' => $this->break_start,
            'break_end' => $this->break_end,
            'break_minutes' => $this->break_minutes,
            'working_hours' => $this->working_hours,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'max_overtime_request_time' => $this->max_overtime_request_time,
            'overtime_rules' => $this->overtime_rules,
            'cross_midnight' => $this->cross_midnight,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

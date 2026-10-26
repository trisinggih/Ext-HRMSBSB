<?php

namespace App\Http\Resources\Shift;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'employee_id' => $this->employee->employee_id,
            ]),
            'shift_id' => $this->shift_id,
            'shift' => $this->shift ? new ShiftResource($this->shift) : null,
            'effective_date' => $this->effective_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

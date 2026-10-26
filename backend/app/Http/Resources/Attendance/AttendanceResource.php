<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->name),
            'shift_id' => $this->shift_id,
            'shift_name' => $this->whenLoaded('shift', fn () => $this->shift?->name),
            'shift_start' => $this->whenLoaded('shift', fn () => $this->shift?->clock_in),
            'shift_end' => $this->whenLoaded('shift', fn () => $this->shift?->clock_out),
            'date' => $this->date?->toDateString(),
            'clock_in_at' => $this->clock_in_at?->toISOString(),
            'clock_out_at' => $this->clock_out_at?->toISOString(),
            'clock_in_photo' => $this->clock_in_photo ? asset('storage/' . $this->clock_in_photo) : null,
            'clock_out_photo' => $this->clock_out_photo ? asset('storage/' . $this->clock_out_photo) : null,
            'employee_photo' => $this->whenLoaded('employee', fn () => $this->employee->photo ? asset('storage/' . $this->employee->photo) : ($this->employee->face_photo ? asset('storage/' . $this->employee->face_photo) : null)),
            'break_start_at' => $this->break_start_at?->toISOString(),
            'break_end_at' => $this->break_end_at?->toISOString(),
            'break_minutes' => $this->break_minutes,
            'working_hours' => is_numeric($this->working_hours) ? (float) $this->working_hours : 0,
            'late_minutes' => $this->late_minutes,
            'overtime_minutes' => $this->overtime_minutes,
            'status' => $this->status,
            'notes' => $this->notes,
            'late_reason' => $this->notes,
            'is_face_verified' => $this->is_face_verified,
            'logs' => AttendanceLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

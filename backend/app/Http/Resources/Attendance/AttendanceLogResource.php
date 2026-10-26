<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_id' => $this->attendance_id,
            'action' => $this->action,
            'action_at' => $this->action_at?->toISOString(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'device_info' => $this->device_info,
            'is_face_verified' => $this->is_face_verified,
        ];
    }
}

<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'creator_id' => $this->creator_id,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'employee_id' => $this->creator->employee_id,
                'name' => $this->creator->name,
            ]),
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date?->toDateString(),
            'priority' => $this->priority,
            'progress' => $this->progress,
            'status' => $this->status,
            'attachment' => $this->attachment,
            'attachment_url' => $this->attachment ? asset('storage/' . $this->attachment) : null,
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'assignees' => $this->whenLoaded('assignees', fn () => $this->assignees->map(fn ($e) => [
                'id' => $e->id,
                'employee_id' => $e->employee_id,
                'name' => $e->name,
                'role' => $e->pivot->role,
            ])),
        ];
    }
}

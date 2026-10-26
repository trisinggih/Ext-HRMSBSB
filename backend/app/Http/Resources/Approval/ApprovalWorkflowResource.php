<?php

namespace App\Http\Resources\Approval;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalWorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'module' => $this->module,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'steps' => ApprovalStepResource::collection($this->whenLoaded('steps')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

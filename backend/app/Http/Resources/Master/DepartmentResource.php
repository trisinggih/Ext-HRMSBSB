<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->relationLoaded('branch') && $this->branch ? $this->branch->company_id : null,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'manager_id' => $this->manager_id,
            'manager_name' => $this->whenLoaded('manager', fn () => $this->manager->name),
            'manager_position_id' => $this->manager_position_id,
            'manager_position_name' => $this->whenLoaded('managerPosition', fn () => $this->managerPosition->name),
            'parent_id' => $this->parent_id,
            'parent_name' => $this->whenLoaded('parent', fn () => $this->parent->name),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

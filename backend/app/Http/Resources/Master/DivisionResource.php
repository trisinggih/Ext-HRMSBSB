<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DivisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->relationLoaded('branch') && $this->branch ? $this->branch->company_id : null,
            'department_id' => $this->department_id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

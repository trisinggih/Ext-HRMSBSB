<?php

namespace App\Http\Resources\Approval;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'approver_type' => $this->approver_type,
            'approver_id' => $this->approver_id,
            'order' => $this->order,
            'is_optional' => $this->is_optional,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

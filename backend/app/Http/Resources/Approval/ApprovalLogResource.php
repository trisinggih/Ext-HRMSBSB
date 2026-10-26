<?php

namespace App\Http\Resources\Approval;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approval_request_id' => $this->approval_request_id,
            'approval_step_id' => $this->approval_step_id,
            'admin_id' => $this->admin_id,
            'approver_name' => $this->approver_name,
            'action' => $this->action,
            'note' => $this->note,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

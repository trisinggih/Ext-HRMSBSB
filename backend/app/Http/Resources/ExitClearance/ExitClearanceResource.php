<?php

namespace App\Http\Resources\ExitClearance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExitClearanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'termination_id' => $this->termination_id,
            'termination' => $this->whenLoaded('termination', fn () => [
                'id' => $this->termination->id,
                'type' => $this->termination->type,
                'termination_date' => $this->termination->termination_date?->toDateString(),
                'status' => $this->termination->status,
                'employee_id' => $this->termination->employee_id,
                'employee' => $this->when(isset($this->termination->employee), fn () => [
                    'id' => $this->termination->employee->id,
                    'employee_id' => $this->termination->employee->employee_id,
                    'name' => $this->termination->employee->name,
                ]),
            ]),
            'item' => $this->item,
            'is_returned' => $this->is_returned,
            'note' => $this->note,
            'returned_at' => $this->returned_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

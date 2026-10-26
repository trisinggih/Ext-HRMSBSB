<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'period' => $this->period,
            'effective_date' => $this->effective_date?->toDateString(),
            'is_active' => $this->is_active,
            'brackets' => TaxBracketResource::collection($this->whenLoaded('brackets')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

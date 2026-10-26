<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxBracketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_rule_id' => $this->tax_rule_id,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'rate' => $this->rate,
        ];
    }
}

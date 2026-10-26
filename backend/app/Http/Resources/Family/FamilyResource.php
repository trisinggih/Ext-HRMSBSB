<?php

namespace App\Http\Resources\Family;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'relation' => $this->relation,
            'name' => $this->name,
            'birth_date' => $this->birth_date?->toDateString(),
            'occupation' => $this->occupation,
            'domicile_address' => $this->domicile_address,
            'residential_address' => $this->residential_address,
            'is_dependent' => $this->is_dependent,
            'is_tax_dependent' => $this->is_tax_dependent,
            'phone' => $this->phone,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

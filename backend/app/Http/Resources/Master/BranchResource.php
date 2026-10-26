<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'province' => $this->province,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'attendance_radius' => $this->attendance_radius,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo' => $this->logo,
            'logo_url' => $this->logo_url ?: $this->effective_logo_url,
            'is_active' => $this->is_active,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

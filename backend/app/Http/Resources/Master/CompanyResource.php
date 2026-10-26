<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'npwp' => $this->npwp,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'kiosk_pin' => $this->kiosk_pin,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'logo' => $this->effective_logo_url,
            'is_active' => $this->is_active,
            'group_id' => $this->group_id,
            'group' => $this->whenLoaded('group', function () {
                return [
                    'id' => $this->group->id,
                    'name' => $this->group->name,
                ];
            }),
            'parent_company_id' => $this->parent_company_id,
            'parent_company' => $this->whenLoaded('parentCompany', function () {
                return $this->parentCompany ? [
                    'id' => $this->parentCompany->id,
                    'name' => $this->parentCompany->name,
                    'legal_name' => $this->parentCompany->legal_name,
                ] : null;
            }),
            'child_companies' => $this->whenLoaded('childCompanies', function () {
                return $this->childCompanies->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'legal_name' => $child->legal_name,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

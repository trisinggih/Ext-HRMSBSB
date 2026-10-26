<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => 'nullable|integer|exists:company_groups,id',
            'parent_company_id' => 'nullable|integer|exists:companies,id',
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'kiosk_pin' => 'nullable|string|max:6',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'logo' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('parent_company_id') && ($this->parent_company_id === '' || $this->parent_company_id === 'null')) {
            $this->merge([
                'parent_company_id' => null,
            ]);
        }
    }
}

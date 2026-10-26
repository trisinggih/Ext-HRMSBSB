<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $val = $this->is_active;
            if ($val === 'true' || $val === '1' || $val === 1 || $val === true) {
                $this->merge(['is_active' => true]);
            } elseif ($val === 'false' || $val === '0' || $val === 0 || $val === false) {
                $this->merge(['is_active' => false]);
            } elseif ($val === null || $val === '' || $val === 'null') {
                $this->merge(['is_active' => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'company_id' => 'sometimes|required|integer|exists:companies,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'attendance_radius' => 'nullable|integer|min:1',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable',
            'is_active' => 'nullable|boolean',
        ];
    }
}

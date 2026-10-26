<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'relation' => 'sometimes|in:spouse,husband,wife,child,parent,other',
            'name' => 'sometimes|string|max:255',
            'birth_date' => 'nullable|date',
            'occupation' => 'nullable|string|max:255',
            'is_dependent' => 'sometimes|boolean',
            'is_tax_dependent' => 'sometimes|boolean',
            'phone' => 'nullable|string|max:20',
            'domicile_address' => 'nullable|string',
            'residential_address' => 'nullable|string',
        ];
    }
}

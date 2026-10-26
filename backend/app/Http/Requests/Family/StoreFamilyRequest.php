<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'nullable|integer|exists:employees,id',
            'relation' => 'sometimes|in:spouse,husband,wife,child,parent,other',
            'name' => 'required|string|max:255',
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

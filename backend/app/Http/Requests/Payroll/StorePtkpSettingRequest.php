<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StorePtkpSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|max:10',
            'name' => 'nullable|string|max:255',
            'ptkp_amount' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

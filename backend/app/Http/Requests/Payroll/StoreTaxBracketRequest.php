<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxBracketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gt:min_amount',
            'rate' => 'required|numeric|min:0|max:100',
        ];
    }
}

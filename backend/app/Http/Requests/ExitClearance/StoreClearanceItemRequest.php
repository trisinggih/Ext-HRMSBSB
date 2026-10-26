<?php

namespace App\Http\Requests\ExitClearance;

use Illuminate\Foundation\Http\FormRequest;

class StoreClearanceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
        ];
    }
}

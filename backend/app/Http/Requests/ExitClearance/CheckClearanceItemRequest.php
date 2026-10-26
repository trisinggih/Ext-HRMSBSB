<?php

namespace App\Http\Requests\ExitClearance;

use Illuminate\Foundation\Http\FormRequest;

class CheckClearanceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checked' => 'sometimes|boolean',
            'note' => 'nullable|string|max:255',
        ];
    }
}

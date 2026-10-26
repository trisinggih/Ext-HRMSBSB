<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCmsSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|max:255',
            'value' => 'nullable|string',
            'group' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ];
    }
}

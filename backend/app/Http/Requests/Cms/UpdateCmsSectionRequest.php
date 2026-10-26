<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_id' => 'nullable|integer|exists:cms_pages,id',
            'key' => 'sometimes|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_url' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}

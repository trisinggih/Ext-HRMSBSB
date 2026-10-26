<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $page = $this->route('page');

        return [
            'slug' => 'sometimes|string|max:255|unique:cms_pages,slug,' . $page->id,
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ];
    }
}

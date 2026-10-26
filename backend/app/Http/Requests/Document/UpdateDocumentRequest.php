<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => 'sometimes|string|max:255',
            'file' => 'sometimes|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}

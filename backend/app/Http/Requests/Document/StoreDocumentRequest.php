<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'nullable|integer|exists:employees,id',
            'document_type' => 'required|string|max:255',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}

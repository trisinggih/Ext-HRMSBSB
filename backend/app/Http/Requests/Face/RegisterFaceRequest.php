<?php

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'face_template' => 'required|string',
            'face_image' => 'nullable|string',
            'model_version' => 'nullable|string|max:255',
        ];
    }
}

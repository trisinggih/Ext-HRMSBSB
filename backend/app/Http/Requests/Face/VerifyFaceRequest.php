<?php

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class VerifyFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'face_token' => 'required|string',
        ];
    }
}

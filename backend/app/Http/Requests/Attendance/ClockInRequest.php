<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',
            'accuracy'    => 'required|numeric|min:0',
            'device_info' => 'nullable|string|max:255',
            'face_token'  => 'nullable|string',
            'face_photo'  => 'nullable|string', // base64 encoded photo
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude wajib diisi.',
            'longitude.required' => 'Longitude wajib diisi.',
            'accuracy.required' => 'Akurasi GPS wajib diisi.',
        ];
    }
}

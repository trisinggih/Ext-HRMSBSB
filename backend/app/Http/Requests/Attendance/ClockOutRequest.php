<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'accuracy'    => 'nullable|numeric|min:0',
            'device_info' => 'nullable|string|max:255',
            'note'        => 'nullable|string|max:255',
            'face_photo'  => 'nullable|string', // base64 encoded photo
        ];
    }
}

<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class InviteTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_id' => 'required|integer|exists:tests,id',
            'notes' => 'nullable|string',
        ];
    }
}

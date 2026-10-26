<?php

namespace App\Http\Requests\Warning;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warning_type' => 'sometimes|in:sp1,sp2,sp3,warning',
            'issue_date' => 'sometimes|date',
            'reason' => 'sometimes|string',
            'expired_date' => 'nullable|date',
            'status' => 'sometimes|in:active,expired,revoked',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}

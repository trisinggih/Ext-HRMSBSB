<?php

namespace App\Http\Requests\Warning;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'warning_type' => 'sometimes|in:sp1,sp2,sp3,warning',
            'issue_date' => 'required|date',
            'reason' => 'required|string',
            'expired_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'sometimes|in:active,expired,revoked',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}

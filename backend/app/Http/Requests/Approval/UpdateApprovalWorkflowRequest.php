<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApprovalWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'module' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

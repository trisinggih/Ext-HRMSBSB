<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'module' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

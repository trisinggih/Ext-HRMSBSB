<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workflow_id' => 'required|integer|exists:approval_workflows,id',
            'approver_type' => 'required|string|max:100',
            'approver_id' => 'nullable|integer',
            'order' => 'required|integer|min:1',
            'is_optional' => 'sometimes|boolean',
        ];
    }
}

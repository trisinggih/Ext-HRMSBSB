<?php

namespace App\Http\Requests\Termination;

use Illuminate\Foundation\Http\FormRequest;

class StoreTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'type' => 'sometimes|in:resignation,contract_end,retirement,termination,phk,other',
            'termination_date' => 'required|date',
            'reason' => 'nullable|string',
            'status' => 'sometimes|in:proposed,approved,completed,cancelled',
        ];
    }
}

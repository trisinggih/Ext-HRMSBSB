<?php

namespace App\Http\Requests\Termination;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|in:resignation,contract_end,retirement,termination,phk,other',
            'termination_date' => 'sometimes|date',
            'reason' => 'nullable|string',
            'status' => 'sometimes|in:proposed,approved,completed,cancelled',
        ];
    }
}

<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'sometimes|integer|exists:employees,id',
            'shift_id' => 'sometimes|integer|exists:shifts,id',
            'effective_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

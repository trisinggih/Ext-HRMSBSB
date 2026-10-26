<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'shift_id' => 'required|integer|exists:shifts,id',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

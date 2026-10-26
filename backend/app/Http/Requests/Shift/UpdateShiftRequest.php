<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $timeFields = ['clock_in', 'clock_out', 'break_start', 'break_end', 'max_overtime_request_time'];
        foreach ($timeFields as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $val = $this->input($field);
                if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $val)) {
                    $this->merge([$field => substr($val, 0, 5)]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'clock_in' => 'sometimes|date_format:H:i',
            'clock_out' => 'sometimes|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
            'working_hours' => 'nullable|integer|min:1|max:24',
            'late_tolerance_minutes' => 'nullable|integer|min:0',
            'max_overtime_request_time' => 'nullable|date_format:H:i',
            'overtime_rules' => 'nullable|array',
            'cross_midnight' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }
}

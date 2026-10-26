<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:leave_types,code,' . $this->route('leave_type')->id,
            'category' => 'sometimes|in:annual,maternity,marriage,special,public,unpaid,custom',
            'default_days' => 'sometimes|integer|min:0|max:365',
            'is_paid' => 'sometimes|boolean',
            'is_carry_forward' => 'sometimes|boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0|max:365',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

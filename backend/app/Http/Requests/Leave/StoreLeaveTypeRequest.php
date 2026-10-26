<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:leave_types,code',
            'category' => 'required|in:annual,maternity,marriage,special,public,unpaid,custom',
            'default_days' => 'required|integer|min:0|max:365',
            'is_paid' => 'sometimes|boolean',
            'is_carry_forward' => 'sometimes|boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0|max:365',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'nullable|string|max:50|unique:employees,employee_id',
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'email' => 'required|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:6',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'marital_status' => 'nullable|in:single,married',
            'address' => 'nullable|string',
            'domicile_address' => 'nullable|string',
            'residential_address' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ktp_number' => 'nullable|string|max:30',
            'npwp' => 'nullable|string|max:30',
            'ptkp_status' => 'nullable|string|max:10',
            'bpjs_kesehatan' => 'nullable|string|max:30',
            'bpjs_ketenagakerjaan' => 'nullable|string|max:30',
            'assignments' => 'required|array|min:1',
            'assignments.*.company_id' => 'required|integer|exists:companies,id',
            'assignments.*.placement_company_id' => 'nullable|integer|exists:companies,id',
            'assignments.*.branch_id' => 'nullable|integer|exists:branches,id',
            'assignments.*.department_id' => 'nullable|integer|exists:departments,id',
            'assignments.*.division_id' => 'nullable|integer|exists:divisions,id',
            'assignments.*.position_id' => 'nullable|integer|exists:positions,id',
            'assignments.*.supervisor_id' => 'nullable|integer|exists:employees,id',
            'assignments.*.join_date' => 'nullable|date',
            'assignments.*.employment_status' => 'nullable|in:permanent,contract,probation,internship,outsourcing',
            'assignments.*.is_primary' => 'boolean',
            'assignments.*.salary_cycle_type' => 'nullable|in:MONTHLY_PAID_MONTHLY,DAILY_PAID_MONTHLY,DAILY_PAID_WEEKLY,DAILY_PAID_DAILY',
            'assignments.*.payroll_components' => 'nullable|array',
            'assignments.*.payroll_components.*.payroll_component_id' => 'required_with:assignments.*.payroll_components|integer|exists:payroll_components,id',
            'assignments.*.payroll_components.*.fixed_amount' => 'nullable|numeric',
            'is_active' => 'sometimes|boolean',
            'can_clock_in_anywhere' => 'nullable|boolean',
            'attendance_location_type' => 'nullable|string|in:assigned_branch,specific_branches,all_branches,anywhere',
            'allowed_branch_ids' => 'nullable|array',
            'allowed_branch_ids.*' => 'integer|exists:branches,id',
            'mobile_access_status' => 'nullable|in:pending,approved,rejected',
            'is_daily_worker' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $assignments = $this->assignments;
        if ($this->has('assignments') && is_string($this->assignments)) {
            $decoded = json_decode($this->assignments, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $assignments = $decoded;
            }
        }

        $merges = [];
        if (is_array($assignments)) {
            foreach ($assignments as &$assign) {
                if (isset($assign['placement_company_id']) && ($assign['placement_company_id'] === '' || $assign['placement_company_id'] === 'null')) {
                    $assign['placement_company_id'] = null;
                }
            }
            $merges['assignments'] = $assignments;
        }

        $isAnywhere = false;
        if ($this->has('can_clock_in_anywhere') && $this->boolean('can_clock_in_anywhere')) {
            $isAnywhere = true;
        } elseif ($this->input('attendance_location_type') === 'anywhere') {
            $isAnywhere = true;
        }

        if ($isAnywhere) {
            $merges['can_clock_in_anywhere'] = true;
            $merges['attendance_location_type'] = 'anywhere';
        } else {
            if ($this->has('can_clock_in_anywhere')) {
                $merges['can_clock_in_anywhere'] = false;
            }
            if ($this->has('attendance_location_type')) {
                $merges['attendance_location_type'] = $this->attendance_location_type;
            }
        }

        if ($this->has('allowed_branch_ids')) {
            $val = $this->allowed_branch_ids;
            if (is_string($val)) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $val = $decoded;
                } elseif (str_contains($val, ',')) {
                    $val = array_filter(array_map('trim', explode(',', $val)));
                } elseif (empty($val) || $val === '[]') {
                    $val = [];
                }
            }
            if (is_array($val)) {
                $merges['allowed_branch_ids'] = array_values(array_map('intval', array_filter($val, fn($v) => is_numeric($v) && (int)$v > 0)));
                if (!$isAnywhere) {
                    $merges['attendance_location_type'] = 'specific_branches';
                }
            }
        }

        if (!empty($merges)) {
            $this->merge($merges);
        }
    }
}

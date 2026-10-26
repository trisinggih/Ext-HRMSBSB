<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use SoftDeletes;

    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'employee_id', 'name', 'nickname', 'email', 'phone', 'password', 'birth_date', 'gender',
        'address', 'domicile_address', 'residential_address', 'photo', 'ktp_number', 'npwp', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'ptkp_status', 'marital_status',
        'is_active',
        'mobile_access_status', 'can_clock_in_anywhere', 'last_latitude', 'last_longitude',
        'face_photo', 'face_registered_at', 'is_daily_worker', 'fcm_token',
        'web_access_enabled', 'web_role', 'managed_division_ids',
        'attendance_location_type', 'allowed_branch_ids',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = [
        'photo_url',
        'department_group',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($this->photo);

        if (request()->hasHeader('Host')) {
            $currentHost = request()->getSchemeAndHttpHost();
            $url = preg_replace('#^https?://[^/]+#', $currentHost, $url);
        }

        return $url;
    }

    public function getDepartmentGroupAttribute(): string
    {
        $assignment = $this->primaryAssignment ?? $this->assignments()->first();
        $dept = strtolower($assignment?->department?->name ?? $this->department?->name ?? '');
        $div  = strtolower($assignment?->division?->name ?? $this->division?->name ?? '');

        if (str_contains($dept, 'fat') || str_contains($dept, 'finance') || str_contains($dept, 'accounting') || str_contains($dept, 'tax') ||
            str_contains($div, 'fat') || str_contains($div, 'finance') || str_contains($div, 'accounting') || str_contains($div, 'tax')) {
            return 'FAT';
        }
        if (str_contains($dept, 'hr') || str_contains($dept, 'ga') ||
            str_contains($div, 'hr') || str_contains($div, 'ga')) {
            return 'HRGA';
        }
        if (str_contains($dept, 'sales') || str_contains($dept, 'marketing') ||
            str_contains($div, 'sales') || str_contains($div, 'marketing') || str_contains($div, 'content') || str_contains($div, 'corporate communication')) {
            return 'Sales';
        }
        if (str_contains($dept, 'operation') || str_contains($dept, 'logistik') || str_contains($dept, 'purchasing') ||
            str_contains($div, 'operation') || str_contains($div, 'gudang') || str_contains($div, 'bengkel') || str_contains($div, 'toko') || str_contains($div, 'logistik') || str_contains($div, 'inventory')) {
            return 'Operasional';
        }

        return 'Operasional';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'can_clock_in_anywhere' => 'boolean',
            'is_daily_worker' => 'boolean',
            'web_access_enabled' => 'boolean',
            'managed_division_ids' => 'array',
            'allowed_branch_ids' => 'array',
        ];
    }

    /**
     * Dapatkan daftar Branch yang diizinkan untuk absensi karyawan ini.
     */
    public function getAllowedBranches(): \Illuminate\Database\Eloquent\Collection
    {
        // 1. Jika bebas presensi / anywhere
        if ($this->can_clock_in_anywhere || $this->attendance_location_type === 'anywhere') {
            return \App\Models\Branch::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '!=', '')
                ->where('longitude', '!=', '')
                ->get(['id', 'name', 'latitude', 'longitude', 'attendance_radius']);
        }

        // 2. Jika konfigurasi cabang spesifik pernah disimpan (berupa array)
        if (is_array($this->allowed_branch_ids)) {
            if (empty($this->allowed_branch_ids)) {
                return new \Illuminate\Database\Eloquent\Collection();
            }
            return \App\Models\Branch::whereIn('id', $this->allowed_branch_ids)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '!=', '')
                ->where('longitude', '!=', '')
                ->get(['id', 'name', 'latitude', 'longitude', 'attendance_radius']);
        }

        // 3. Default (belum pernah dibatasi): Seluruh cabang perusahaan bisa digunakan
        return \App\Models\Branch::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->get(['id', 'name', 'latitude', 'longitude', 'attendance_radius']);
    }

    // ===== Web Access Helpers =====

    public function canAccessWeb(): bool
    {
        return $this->web_access_enabled && !empty($this->web_role);
    }

    public function hasWebRole(string $role): bool
    {
        return $this->web_role === $role;
    }

    public function isSuperAdmin(): bool { return $this->web_role === 'super_admin'; }
    public function isStaffHr(): bool { return $this->web_role === 'staff_hr'; }
    public function isSpv(): bool { return $this->web_role === 'spv'; }

    /**
     * Cek apakah karyawan ini memiliki bawahan (merupakan atasan/supervisor).
     */
    public function hasSubordinates(): bool
    {
        // 1. Cek dari employee_assignments yang merujuk supervisor_id ke karyawan ini
        $hasAssignmentSubordinates = \App\Models\EmployeeAssignment::where('supervisor_id', $this->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasAssignmentSubordinates) {
            return true;
        }

        // 2. Cek apakah di tabel employees terdapat kolom supervisor_id atau atasan_id
        if (\Illuminate\Support\Facades\Schema::hasColumn('employees', 'supervisor_id')) {
            if (\App\Models\Employee::where('supervisor_id', $this->id)->whereNull('deleted_at')->exists()) {
                return true;
            }
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('employees', 'atasan_id')) {
            if (\App\Models\Employee::where('atasan_id', $this->id)->whereNull('deleted_at')->exists()) {
                return true;
            }
        }

        // 3. Cek apakah karyawan ini merupakan manager departemen
        if (\App\Models\Department::where('manager_id', $this->id)->exists()) {
            return true;
        }

        // 4. Role khusus yang memiliki kewenangan manajemen (super_admin / spv)
        if (in_array($this->web_role, ['super_admin', 'spv'])) {
            return true;
        }

        return false;
    }

    public function subordinatesCount(): int
    {
        return \App\Models\EmployeeAssignment::where('supervisor_id', $this->id)
            ->whereNull('deleted_at')
            ->count();
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class, 'supervisor_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }
    
    // Helper to get primary assignment
    public function primaryAssignment()
    {
        return $this->hasOne(EmployeeAssignment::class)->where('is_primary', true);
    }

    public function position()
    {
        return $this->hasOneThrough(
            Position::class,
            EmployeeAssignment::class,
            'employee_id',
            'id',
            'id',
            'position_id'
        )->where('employee_assignments.is_primary', true);
    }

    public function positions()
    {
        return $this->hasManyThrough(
            Position::class,
            EmployeeAssignment::class,
            'employee_id',
            'id',
            'id',
            'position_id'
        );
    }

    public function division()
    {
        return $this->hasOneThrough(
            Division::class,
            EmployeeAssignment::class,
            'employee_id',
            'id',
            'id',
            'division_id'
        )->where('employee_assignments.is_primary', true);
    }

    public function department()
    {
        return $this->hasOneThrough(
            Department::class,
            EmployeeAssignment::class,
            'employee_id',
            'id',
            'id',
            'department_id'
        )->where('employee_assignments.is_primary', true);
    }

    public function branch()
    {
        return $this->hasOneThrough(
            Branch::class,
            EmployeeAssignment::class,
            'employee_id',
            'id',
            'id',
            'branch_id'
        )->where('employee_assignments.is_primary', true);
    }

    public function company()
    {
        return $this->hasOneThrough(
            Company::class,
            EmployeeAssignment::class,
            'employee_id',
            'id',
            'id',
            'company_id'
        )->where('employee_assignments.is_primary', true);
    }

    public function getPositionIdAttribute()
    {
        return $this->primaryAssignment?->position_id;
    }

    public function getDivisionIdAttribute()
    {
        return $this->primaryAssignment?->division_id;
    }

    public function getSupervisorIdAttribute()
    {
        return $this->primaryAssignment?->supervisor_id;
    }

    public function families(): HasMany
    {
        return $this->hasMany(EmployeeFamily::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function faces(): HasMany
    {
        return $this->hasMany(EmployeeFace::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(EmployeeDevice::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payrollSettings()
    {
        return $this->hasOne(EmployeePayrollSetting::class);
    }

    public function salaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'employee_id');
    }

    public function taxSetting()
    {
        return $this->hasOne(TaxEmployeeSetting::class, 'employee_id');
    }

    public function payrollRuns()
    {
        return $this->hasMany(PayrollRun::class, 'employee_id');
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class, 'employee_id');
    }

    public function tasks()
    {
        return $this->hasManyThrough(Task::class, TaskAssignment::class, 'employee_id', 'id', 'id', 'task_id');
    }

    public function locationTrackingSessions(): HasMany
    {
        return $this->hasMany(LocationTrackingSession::class);
    }

    public function latestLocation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeLocation::class)->latestOfMany('recorded_at');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(EmployeeWarning::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(\App\Models\EmployeeSignature::class);
    }

    public function latestSignature(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\EmployeeSignature::class)->latestOfMany();
    }

    public function getSignatureBase64Attribute(): ?string
    {
        return $this->latestSignature?->signature_base64;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->latestSignature?->signature_url;
    }

    public function terminations(): HasMany
    {
        return $this->hasMany(EmployeeTermination::class);
    }

    public function latestTermination(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeTermination::class)->latestOfMany();
    }

    public function exitClearances(): HasMany
    {
        return $this->hasManyThrough(TerminationClearance::class, EmployeeTermination::class);
    }

    public function recalculatePtkpStatus()
    {
        $prefix = $this->marital_status === 'married' ? 'K' : 'TK';
        $dependents = $this->families()->where('is_tax_dependent', true)->count();
        $dependents = min($dependents, 3);
        
        $newStatus = $prefix . '/' . $dependents;
        
        if ($this->ptkp_status !== $newStatus) {
            $this->ptkp_status = $newStatus;
            $this->saveQuietly(); // Use saveQuietly to prevent infinite loops if called from updating event
        }
    }

    public function scopeWithoutExecutives($query)
    {
        return $query->whereDoesntHave('assignments.position', function ($pq) {
            $pq->whereIn('name', ['CEO', 'CFO'])
              ->orWhereIn('code', ['CEO', 'CFO'])
              ->orWhere('name', 'like', '%CEO%')
              ->orWhere('name', 'like', '%CFO%')
              ->orWhere('code', 'like', '%CEO%')
              ->orWhere('code', 'like', '%CFO%');
        });
    }

    public function getEmploymentStatusAttribute(): ?string
    {
        return $this->primaryAssignment?->employment_status;
    }

    public function isExecutive(): bool
    {
        $assignments = $this->relationLoaded('assignments') ? $this->assignments : $this->assignments()->with(['position', 'department'])->get();
        foreach ($assignments as $a) {
            $deptName = strtoupper(trim($a->department?->name ?? ''));
            $deptCode = strtoupper(trim($a->department?->code ?? ''));
            $posName  = strtoupper(trim($a->position?->name ?? ''));
            $posCode  = strtoupper(trim($a->position?->code ?? ''));
            if (in_array($deptName, ['CEO', 'CFO']) 
                || in_array($deptCode, ['CEO', 'CFO']) 
                || in_array($posName, ['CEO', 'CFO']) 
                || in_array($posCode, ['CEO', 'CFO'])
                || str_contains($posName, 'CHIEF EXECUTIVE')
                || str_contains($posName, 'CHIEF FINANCIAL')
                || in_array($posName, ['DIREKTUR UTAMA', 'DIREKTUR KEUANGAN'])) {
                return true;
            }
        }
        return in_array($this->id, [268, 335]);
    }
}


<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EmployeeWebAuthController extends Controller
{
    /**
     * Login karyawan ke web HRMS.
     * Terpisah dari mobile login — token menggunakan ability 'web'.
     */
    public function login(Request $request): JsonResponse
    {
        $identifier = $request->input('identifier') ?? $request->input('email') ?? $request->input('employee_id');
        if (empty($identifier)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau ID Karyawan wajib diisi.'],
            ]);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        // 1. Cek apakah identifier adalah akun Administrator (Admin HRMS)
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $admin = \App\Models\Admin::where('email', $identifier)->first();
            if ($admin && Hash::check($request->password, $admin->password)) {
                if (!$admin->is_active) {
                    throw ValidationException::withMessages([
                        'email' => ['Akun Administrator ini dinonaktifkan.'],
                    ]);
                }

                $token = $admin->createToken('admin-auth', ['*'])->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil sebagai Administrator',
                    'data'    => [
                        'token'     => $token,
                        'user_type' => 'admin',
                        'is_head'   => false,
                        'is_hr'     => true,
                        'admin'     => [
                            'id'    => $admin->id,
                            'name'  => $admin->name,
                            'email' => $admin->email,
                            'role'  => $admin->role ?? 'admin',
                        ],
                    ],
                ]);
            }
        }

        // 2. Cek akun Karyawan (Employees)
        $employee = Employee::with([
            'primaryAssignment.company',
            'primaryAssignment.branch',
            'primaryAssignment.department',
            'primaryAssignment.division',
            'primaryAssignment.position',
            'positions',
        ])->where(function ($q) use ($identifier) {
            $q->where('email', $identifier)
              ->orWhere('employee_id', $identifier);
        })->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid. Silakan periksa kembali email/ID dan password Anda.'],
            ]);
        }

        if (!$employee->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun karyawan ini tidak aktif.'],
            ]);
        }

        $primaryAssignment = $employee->primaryAssignment;
        $positionName = $primaryAssignment?->position?->name ?? '';
        $deptName     = $primaryAssignment?->department?->name ?? '';
        $divName      = $primaryAssignment?->division?->name ?? '';

        $allPositionNames = $employee->positions->pluck('name')->filter()->unique()->join(' ');
        $combinedPosition = trim($positionName . ' ' . $allPositionNames);

        // 1. Cek keterikatan karyawan dengan tim HR & GA (Departemen, Divisi, Jabatan, atau Penugasan)
        $hasHrGaAssignment = \App\Models\EmployeeAssignment::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->whereHas('department', function ($dq) {
                    $dq->where('name', 'LIKE', '%HR%')
                       ->orWhere('name', 'LIKE', '%Human Resource%')
                       ->orWhere('name', 'LIKE', '%General Affair%')
                       ->orWhere('name', 'LIKE', '%GA%')
                       ->orWhere('name', 'LIKE', '%Personalia%')
                       ->orWhere('name', 'LIKE', '%SDM%');
                })->orWhereHas('division', function ($vq) {
                    $vq->where('name', 'LIKE', '%HR%')
                       ->orWhere('name', 'LIKE', '%Human Resource%')
                       ->orWhere('name', 'LIKE', '%General Affair%')
                       ->orWhere('name', 'LIKE', '%GA%')
                       ->orWhere('name', 'LIKE', '%Personalia%')
                       ->orWhere('name', 'LIKE', '%SDM%');
                })->orWhereHas('position', function ($pq) {
                    $pq->where('name', 'LIKE', '%HR%')
                       ->orWhere('name', 'LIKE', '%Human Resource%')
                       ->orWhere('name', 'LIKE', '%General Affair%')
                       ->orWhere('name', 'LIKE', '%GA%')
                       ->orWhere('name', 'LIKE', '%Personalia%')
                       ->orWhere('name', 'LIKE', '%SDM%');
                });
            })->exists();

        $isHrGa = $hasHrGaAssignment
            || (bool) preg_match('/(hr|human resource|general affair|\bga\b|personalia|sdm)/i', $deptName . ' ' . $divName . ' ' . $combinedPosition)
            || $employee->web_role === 'super_admin';

        // 2. Cek apakah karyawan adalah Atasan / Manager / Supervisor
        $subordinatesCount = \App\Models\EmployeeAssignment::where('supervisor_id', $employee->id)->count();
        $isDeptManager     = \App\Models\Department::where('manager_id', $employee->id)->exists();
        $hasManagedDiv     = !empty($employee->managed_division_ids);
        $isHeadTitle       = (bool) preg_match('/(kepala|head|manager|supervisor|\bspv\b|lead|leader|direktur|director|chief|vp|koordinator|kabag|kasubag)/i', $combinedPosition);

        $isHead = ($subordinatesCount > 0) || $isDeptManager || $hasManagedDiv || $isHeadTitle || $employee->isSpv();

        $portalType = $request->input('portal_type'); // 'hrms' | 'leadership' | null

        // 3. Batasi Login HRMS: HANYA karyawan HR & GA yang dapat login ke portal HRMS
        if ($portalType !== 'leadership') {
            if (!$isHrGa) {
                throw ValidationException::withMessages([
                    'email' => ['Akses ditolak. Portal HRMS hanya dapat diakses oleh karyawan dari tim HR & GA.'],
                ]);
            }
        } else {
            if (!$isHead && !$isHrGa) {
                throw ValidationException::withMessages([
                    'email' => ['Akses ditolak. Anda tidak memiliki hak akses sebagai atasan atau tim HR & GA.'],
                ]);
            }
        }

        // 4. Penentuan Hak Akses Menu:
        // - Manager & Supervisor HR & GA: Akses penuh (super_admin)
        // - Staff HR & GA: Akses terbatas (staff_hr) — kehadiran, master data, operasional, data karyawan tanpa hitungan gaji, tanpa KPI
        // - Atasan non-HR: Hanya portal penilaian atasan (spv)
        if ($isHrGa) {
            $isManagerOrSpv = $isHeadTitle || ($subordinatesCount > 0) || $isDeptManager || $hasManagedDiv || $employee->web_role === 'super_admin';
            if ($isManagerOrSpv) {
                $webRole = 'super_admin';
            } else {
                $webRole = 'staff_hr';
            }
        } else {
            $webRole = 'spv';
        }

        // Semua token login web menggunakan ability wildcard [*] agar tidak terhambat middleware Sanctum ability
        // Batasan hak akses per peran (Manager/SPV vs Staff) ditegakkan secara ketat lewat web_role dan middleware fitur (KPI, Payroll).
        $tokenAbilities = ['*'];

        // Aktifkan akses web secara otomatis
        if (!$employee->web_access_enabled || $employee->web_role !== $webRole) {
            $employee->update([
                'web_access_enabled' => true,
                'web_role'           => $webRole,
            ]);
            $employee->refresh();
        }

        // Buat token Sanctum untuk web dengan ability sesuai level akses
        $token = $employee->createToken('employee-web', $tokenAbilities, now()->addYear())->plainTextToken;

        // Resolve divisi yang dikelola
        $managedDivisions = [];
        if ($isHead && !empty($employee->managed_division_ids)) {
            $managedDivisions = Division::whereIn('id', $employee->managed_division_ids)
                ->get(['id', 'name'])
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token'     => $token,
                'user_type' => 'employee',
                'is_head'   => $isHead,
                'is_hr'     => $isHrGa,
                'position'  => $positionName,
                'employee'  => [
                    'id'                   => $employee->id,
                    'employee_id'          => $employee->employee_id,
                    'name'                 => $employee->name,
                    'email'                => $employee->email,
                    'photo_url'            => $employee->photo
                        ? Storage::disk('public')->url($employee->photo)
                        : null,
                    'web_role'             => $employee->web_role,
                    'is_head'              => $isHead,
                    'is_hr'                => $isHrGa,
                    'has_subordinates'     => ($subordinatesCount > 0),
                    'subordinates_count'   => $subordinatesCount,
                    'managed_division_ids' => $employee->managed_division_ids ?? [],
                    'managed_divisions'    => $managedDivisions,
                    'company'              => $primaryAssignment?->company?->name,
                    'department'           => $deptName,
                    'position'             => $positionName,
                    'division'             => $divName,
                    'department_group'     => $employee->department_group,
                ],
            ],
        ]);
    }

    /**
     * Return data employee yang sedang login (untuk inisialisasi state frontend).
     */
    public function me(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $primaryAssignment = $employee->primaryAssignment;
        $primaryAssignment?->load(['company', 'branch', 'department', 'division', 'position']);

        $positionName = $primaryAssignment?->position?->name ?? '';
        $deptName     = $primaryAssignment?->department?->name ?? '';
        $divName      = $primaryAssignment?->division?->name ?? '';

        $allPositionNames = $employee->positions->pluck('name')->filter()->unique()->join(' ');
        $combinedPosition = trim($positionName . ' ' . $allPositionNames);

        $hasHrGaAssignment = \App\Models\EmployeeAssignment::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->whereHas('department', function ($dq) {
                    $dq->where('name', 'LIKE', '%HR%')
                       ->orWhere('name', 'LIKE', '%Human Resource%')
                       ->orWhere('name', 'LIKE', '%General Affair%')
                       ->orWhere('name', 'LIKE', '%GA%')
                       ->orWhere('name', 'LIKE', '%Personalia%')
                       ->orWhere('name', 'LIKE', '%SDM%');
                })->orWhereHas('division', function ($vq) {
                    $vq->where('name', 'LIKE', '%HR%')
                       ->orWhere('name', 'LIKE', '%Human Resource%')
                       ->orWhere('name', 'LIKE', '%General Affair%')
                       ->orWhere('name', 'LIKE', '%GA%')
                       ->orWhere('name', 'LIKE', '%Personalia%')
                       ->orWhere('name', 'LIKE', '%SDM%');
                })->orWhereHas('position', function ($pq) {
                    $pq->where('name', 'LIKE', '%HR%')
                       ->orWhere('name', 'LIKE', '%Human Resource%')
                       ->orWhere('name', 'LIKE', '%General Affair%')
                       ->orWhere('name', 'LIKE', '%GA%')
                       ->orWhere('name', 'LIKE', '%Personalia%')
                       ->orWhere('name', 'LIKE', '%SDM%');
                });
            })->exists();

        $isHrGa = $hasHrGaAssignment
            || (bool) preg_match('/(hr|human resource|general affair|\bga\b|personalia|sdm)/i', $deptName . ' ' . $divName . ' ' . $combinedPosition)
            || $employee->web_role === 'super_admin';

        $subordinatesCount = \App\Models\EmployeeAssignment::where('supervisor_id', $employee->id)->count();
        $isDeptManager     = \App\Models\Department::where('manager_id', $employee->id)->exists();
        $hasManagedDiv     = !empty($employee->managed_division_ids);
        $isHeadTitle       = (bool) preg_match('/(kepala|head|manager|supervisor|\bspv\b|lead|leader|direktur|director|chief|vp|koordinator|kabag|kasubag)/i', $combinedPosition);

        $isHead = ($subordinatesCount > 0) || $isDeptManager || $hasManagedDiv || $isHeadTitle || $employee->isSpv();

        if ($isHrGa) {
            $isManagerOrSpv = $isHeadTitle || ($subordinatesCount > 0) || $isDeptManager || $hasManagedDiv || $employee->web_role === 'super_admin';
            $resolvedWebRole = $isManagerOrSpv ? 'super_admin' : 'staff_hr';
        } else {
            $resolvedWebRole = $isHead ? 'spv' : null;
        }

        if ($employee->web_role !== $resolvedWebRole && $resolvedWebRole !== null) {
            $employee->update(['web_role' => $resolvedWebRole]);
            $employee->refresh();
        }

        $managedDivisions = [];
        if ($isHead && !empty($employee->managed_division_ids)) {
            $managedDivisions = Division::whereIn('id', $employee->managed_division_ids)
                ->get(['id', 'name'])
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'user_type' => 'employee',
                'is_head'   => $isHead,
                'is_hr'     => $isHrGa,
                'position'  => $positionName,
                'employee'  => [
                    'id'                   => $employee->id,
                    'employee_id'          => $employee->employee_id,
                    'name'                 => $employee->name,
                    'email'                => $employee->email,
                    'photo_url'            => $employee->photo
                        ? Storage::disk('public')->url($employee->photo)
                        : null,
                    'web_role'             => $employee->web_role,
                    'is_head'              => $isHead,
                    'is_hr'                => $isHrGa,
                    'has_subordinates'     => ($subordinatesCount > 0),
                    'subordinates_count'   => $subordinatesCount,
                    'managed_division_ids' => $employee->managed_division_ids ?? [],
                    'managed_divisions'    => $managedDivisions,
                    'company'              => $primaryAssignment?->company?->name,
                    'department'           => $deptName,
                    'position'             => $positionName,
                    'division'             => $divName,
                    'department_group'     => $employee->department_group,
                ],
            ],
        ]);
    }

    /**
     * Logout dari web session.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
            'data'    => null,
        ]);
    }
}

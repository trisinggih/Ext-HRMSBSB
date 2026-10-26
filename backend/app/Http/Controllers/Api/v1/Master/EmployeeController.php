<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreEmployeeRequest;
use App\Http\Requests\Master\UpdateEmployeeRequest;
use App\Http\Resources\Master\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->when(!$request->boolean('include_executives') && !$request->boolean('for_payroll'), fn ($q) => $q->withoutExecutives())
            ->with(['assignments.company', 'assignments.placementCompany', 'assignments.branch', 'assignments.department.branch', 'assignments.division', 'assignments.position', 'assignments.supervisor', 'latestLocation'])
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('branch_id', $request->branch_id));
            })
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('department_id', $request->department_id));
            })
            ->when($request->filled('division_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('division_id', $request->division_id));
            })
            ->when($request->filled('position_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('position_id', $request->position_id));
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('company_id', $request->company_id));
            })
            ->when($request->filled('employment_status'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('employment_status', $request->employment_status));
            })
            ->when($request->filled('is_active'), function ($q) use ($request) {
                if ($request->is_active !== 'all') {
                    $q->where('is_active', $request->boolean('is_active'));
                }
            }, function ($q) use ($request) {
                if (!$request->boolean('include_inactive')) {
                    $q->where('is_active', true)
                      ->whereDoesntHave('terminations', function ($tq) {
                          $tq->whereIn('status', ['approved', 'completed'])
                             ->whereDate('termination_date', '<=', now()->toDateString());
                      });
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('sort_by'), fn ($q) => $q->orderBy($request->sort_by, $request->get('sort_dir', 'asc')), fn ($q) => $q->orderBy('name'))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar employee',
            'data' => EmployeeResource::collection($employees)->response()->getData(true),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->when(!$request->boolean('include_executives') && !$request->boolean('for_payroll'), fn ($q) => $q->withoutExecutives())
            ->with([
                'assignments.company',
                'assignments.placementCompany',
                'assignments.branch',
                'assignments.department.branch',
                'assignments.division',
                'assignments.position',
                'assignments.supervisor',
                'terminations.clearances',
            ])
            ->where(function ($q) {
                $q->where('is_active', false)
                  ->orWhereHas('terminations', function ($tq) {
                      $tq->whereIn('status', ['approved', 'completed']);
                  });
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('branch_id', $request->branch_id));
            })
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('department_id', $request->department_id));
            })
            ->when($request->filled('division_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('division_id', $request->division_id));
            })
            ->when($request->filled('position_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('position_id', $request->position_id));
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->whereHas('assignments', fn($sq) => $sq->where('company_id', $request->company_id));
            })
            ->when($request->filled('termination_type'), function ($q) use ($request) {
                $q->whereHas('terminations', fn($tq) => $tq->where('type', $request->termination_type));
            })
            ->when($request->filled('start_date'), function ($q) use ($request) {
                $q->whereHas('terminations', fn($tq) => $tq->whereDate('termination_date', '>=', $request->start_date));
            })
            ->when($request->filled('end_date'), function ($q) use ($request) {
                $q->whereHas('terminations', fn($tq) => $tq->whereDate('termination_date', '<=', $request->end_date));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('sort_by'), fn ($q) => $q->orderBy($request->sort_by, $request->get('sort_dir', 'desc')), fn ($q) => $q->orderByDesc('updated_at'))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar history karyawan keluar / terminasi',
            'data' => EmployeeResource::collection($employees)->response()->getData(true),
        ]);
    }

    public function reactivate(Employee $employee): JsonResponse
    {
        $employee->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => "Karyawan {$employee->name} berhasil diaktifkan kembali.",
            'data' => new EmployeeResource($employee->fresh(['assignments.company', 'assignments.branch', 'assignments.department.branch', 'assignments.position'])),
        ]);
    }

    public function withSalaryStructure(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->where(function($q) {
                $q->whereHas('salaryComponents')
                  ->orWhereHas('assignments.position.payrollComponents');
            })
            ->where('is_active', true)
            ->when($request->filled('sort_by'), fn ($q) => $q->orderBy($request->sort_by, $request->get('sort_dir', 'asc')), fn ($q) => $q->orderBy('name'))
            ->paginate($request->integer('per_page', 100));

        return response()->json([
            'success' => true,
            'message' => 'Daftar employee dengan struktur gaji',
            'data' => EmployeeResource::collection($employees)->response()->getData(true),
        ]);
    }

    public function store(StoreEmployeeRequest $request, \App\Services\NipGeneratorService $nipService): JsonResponse
    {
        $data = $request->validated();
        $data['photo'] = $this->uploadPhoto($request);

        if (empty($data['employee_id'])) {
            $joinDate = !empty($data['join_date']) ? \Carbon\Carbon::parse($data['join_date']) : now();
            $data['employee_id'] = $nipService->generateNip($joinDate);
        }

        $employee = Employee::create($data);

        if (!empty($data['assignments'])) {
            foreach ($data['assignments'] as $assignmentData) {
                $components = $assignmentData['payroll_components'] ?? [];
                unset($assignmentData['payroll_components']);
                
                $assignment = $employee->assignments()->create($assignmentData);
                if (!empty($components)) {
                    $syncData = [];
                    foreach ($components as $comp) {
                        $id = is_array($comp) ? ($comp['payroll_component_id'] ?? null) : $comp;
                        if ($id) {
                            $syncData[] = $id;
                        }
                    }
                    $assignment->payrollComponents()->sync($syncData);

                    foreach ($components as $comp) {
                        $compObj = is_array($comp) ? $comp : ['payroll_component_id' => $comp, 'fixed_amount' => 0];
                        $compId = $compObj['payroll_component_id'] ?? null;
                        if (!$compId) continue;

                        $existing = \App\Models\EmployeeSalaryComponent::withTrashed()
                            ->where('employee_assignment_id', $assignment->id)
                            ->where('payroll_component_id', $compId)
                            ->first();

                        if ($existing) {
                            if ($existing->trashed()) {
                                $existing->restore();
                            }
                            $existing->update([
                                'employee_id' => $employee->id,
                                'amount' => $compObj['fixed_amount'] ?? 0,
                                'calculation_type' => 'fixed',
                                'effective_from' => '2026-01-01',
                                'is_active' => true,
                            ]);
                        } else {
                            \App\Models\EmployeeSalaryComponent::create([
                                'employee_id' => $employee->id,
                                'employee_assignment_id' => $assignment->id,
                                'payroll_component_id' => $compId,
                                'amount' => $compObj['fixed_amount'] ?? 0,
                                'calculation_type' => 'fixed',
                                'effective_from' => '2026-01-01',
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee berhasil dibuat',
            'data' => new EmployeeResource($employee->load(['assignments.company', 'assignments.placementCompany', 'assignments.branch', 'assignments.department.branch', 'assignments.division', 'assignments.position', 'assignments.supervisor'])),
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail employee',
            'data' => new EmployeeResource($employee->load(['assignments.company', 'assignments.placementCompany', 'assignments.branch', 'assignments.department.branch', 'assignments.division', 'assignments.position', 'assignments.supervisor'])),
        ]);
    }

    public function details(Employee $employee): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail employee lengkap',
            'data' => new EmployeeResource($employee->load([
                'assignments.company', 'assignments.placementCompany', 'assignments.branch', 'assignments.department.branch', 'assignments.division', 'assignments.position.payrollComponents.component', 'assignments.supervisor', 'assignments.payrollComponents',
                'families', 'documents', 'faces', 'devices', 'shifts', 'payrollSettings',
                'salaryComponents', 'locationTrackingSessions', 'warnings', 'terminations', 'latestSignature'
            ])),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            $data['photo'] = $this->uploadPhoto($request);
        }

        $employee->update($data);

        if (isset($data['assignments'])) {
            $existingAssignmentIds = collect($data['assignments'])->pluck('id')->filter()->toArray();
            $employee->assignments()->whereNotIn('id', $existingAssignmentIds)->delete();
            
            $user = $request->user();
            $isStaffHr = ($user instanceof Employee && $user->web_role === 'staff_hr');

            foreach ($data['assignments'] as $assignmentData) {
                $hasPayrollComponents = array_key_exists('payroll_components', $assignmentData);
                $components = $assignmentData['payroll_components'] ?? null;
                unset($assignmentData['payroll_components']);
                
                if (!empty($assignmentData['id'])) {
                    $assignment = $employee->assignments()->where('id', $assignmentData['id'])->first();
                    if ($assignment) {
                        $assignment->update($assignmentData);
                    }
                } else {
                    $assignment = $employee->assignments()->create($assignmentData);
                }
                
                if (!$isStaffHr && $hasPayrollComponents && is_array($components)) {
                    // Sync to pivot table (only IDs, as fixed_amount is in employee_salary_components)
                    $syncData = [];
                    foreach ($components as $comp) {
                        $id = is_array($comp) ? ($comp['payroll_component_id'] ?? null) : $comp;
                        if ($id) {
                            $syncData[] = $id;
                        }
                    }
                    $assignment->payrollComponents()->sync($syncData);

                    // Simpan ke employee_salary_components 
                    // Struktur components dari frontend: [{payroll_component_id: 1, fixed_amount: 5000000}, ...]
                    
                    // Kumpulkan ID yang dipertahankan
                    $keptIds = [];
                    foreach ($components as $comp) {
                        $compObj = is_array($comp) ? $comp : ['payroll_component_id' => $comp, 'fixed_amount' => 0];
                        $compId = $compObj['payroll_component_id'] ?? null;
                        if (!$compId) continue;

                        $keptIds[] = $compId;

                        $existing = \App\Models\EmployeeSalaryComponent::withTrashed()
                            ->where('employee_assignment_id', $assignment->id)
                            ->where('payroll_component_id', $compId)
                            ->first();

                        if ($existing) {
                            if ($existing->trashed()) {
                                $existing->restore();
                            }
                            $existing->update([
                                'employee_id' => $employee->id,
                                'amount' => $compObj['fixed_amount'] ?? 0,
                                'calculation_type' => 'fixed',
                                'is_active' => true,
                            ]);
                        } else {
                            \App\Models\EmployeeSalaryComponent::create([
                                'employee_id' => $employee->id,
                                'employee_assignment_id' => $assignment->id,
                                'payroll_component_id' => $compId,
                                'amount' => $compObj['fixed_amount'] ?? 0,
                                'calculation_type' => 'fixed',
                                'effective_from' => '2026-01-01',
                                'is_active' => true,
                            ]);
                        }
                    }
                    
                    // Hapus yang tidak diceklis lagi untuk assignment ini secara permanen agar tidak menimbulkan duplicate entry
                    \App\Models\EmployeeSalaryComponent::where('employee_assignment_id', $assignment->id)
                        ->whereNotIn('payroll_component_id', $keptIds)
                        ->forceDelete();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee berhasil diperbarui',
            'data' => new EmployeeResource($employee->fresh()->load(['assignments.company', 'assignments.placementCompany', 'assignments.branch', 'assignments.department.branch', 'assignments.division', 'assignments.position', 'assignments.supervisor'])),
        ]);
    }

    public function approveMobileAccess(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'mobile_access_status' => 'required|in:pending,approved,rejected',
        ]);

        $employee->mobile_access_status = $request->mobile_access_status;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Status akses mobile berhasil diupdate menjadi ' . $employee->mobile_access_status,
            'data' => null,
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
        }
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee berhasil dihapus',
            'data' => null,
        ]);
    }

    /**
     * Daftar device milik seorang employee.
     */
    public function deviceList(Employee $employee): JsonResponse
    {
        $devices = EmployeeDevice::where('employee_id', $employee->id)
            ->orderByRaw("FIELD(status, 'approved', 'pending', 'rejected')")
            ->orderBy('last_active_at', 'desc')
            ->get(['id', 'device_name', 'device_id', 'platform', 'status', 'approved_at', 'last_active_at', 'created_at']);

        return response()->json([
            'success' => true,
            'message' => 'Device list',
            'data'    => $devices,
        ]);
    }

    /**
     * Approve device baru & revoke device lama.
     */
    public function approveDevice(Employee $employee, $deviceId): JsonResponse
    {
        $device = EmployeeDevice::where('employee_id', $employee->id)
            ->where('id', $deviceId)
            ->firstOrFail();

        // Revoke semua device lain (set ke rejected)
        EmployeeDevice::where('employee_id', $employee->id)
            ->where('id', '!=', $device->id)
            ->update(['status' => 'rejected']);

        // Approve device yang dipilih
        $device->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Device '{$device->device_name}' berhasil disetujui. Device lainnya telah dicabut aksesnya.",
            'data'    => $device,
        ]);
    }

    /**
     * Revoke (tolak) sebuah device.
     */
    public function rejectDevice(Employee $employee, $deviceId): JsonResponse
    {
        $device = EmployeeDevice::where('employee_id', $employee->id)
            ->where('id', $deviceId)
            ->firstOrFail();

        $device->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => "Akses device '{$device->device_name}' berhasil dicabut.",
            'data'    => null,
        ]);
    }

    protected function uploadPhoto(Request $request): ?string
    {
        if (!$request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('photos/employees', 'public');
    }

    /**
     * Simulasi perhitungan gaji untuk employee (realtime).
     */
    public function simulatePayroll(Employee $employee, \App\Services\PayrollCalculatorService $calculator): JsonResponse
    {
        // Buat dummy periode untuk bulan berjalan
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        $mockPeriod = new \App\Models\PayrollPeriod([
            'id' => 999999, // dummy ID
            'name' => 'Simulasi ' . now()->translatedFormat('F Y'),
            'period' => now()->format('Y-m'),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'payment_date' => $endDate->toDateString(),
        ]);

        try {
            $calcResult = $calculator->calculateForEmployee($employee, $mockPeriod, true);

            return response()->json([
                'success' => true,
                'message' => 'Simulasi gaji berhasil',
                'data' => $calcResult,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan simulasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function pullSalaryComponents(Employee $employee): \Illuminate\Http\JsonResponse
    {
        $assignment = $employee->assignments()->where('is_primary', true)->first() ?? $employee->assignments()->first();
        if (!$assignment || !$assignment->position_id) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak memiliki jabatan (assignment).'
            ], 400);
        }

        $positionComps = \App\Models\PositionPayrollComponent::where('position_id', $assignment->position_id)->get();
        if ($positionComps->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Jabatan ini tidak memiliki template komponen gaji.'
            ], 400);
        }

        $count = 0;
        foreach ($positionComps as $pc) {
            $existing = \App\Models\EmployeeSalaryComponent::withTrashed()
                ->where('employee_assignment_id', $assignment->id)
                ->where('payroll_component_id', $pc->payroll_component_id)
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update([
                    'employee_id' => $employee->id,
                    'amount' => $pc->fixed_amount,
                    'calculation_type' => 'fixed',
                    'is_active' => true,
                ]);
            } else {
                \App\Models\EmployeeSalaryComponent::create([
                    'employee_id' => $employee->id,
                    'employee_assignment_id' => $assignment->id,
                    'payroll_component_id' => $pc->payroll_component_id,
                    'amount' => $pc->fixed_amount,
                    'calculation_type' => 'fixed',
                    'effective_from' => '2026-01-01',
                    'is_active' => true,
                ]);
            }
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil menarik $count komponen gaji dari jabatan."
        ]);
    }
    public function getAssignments(\App\Models\Employee $employee): \Illuminate\Http\JsonResponse
    {
        $assignments = $employee->assignments()
            ->with(['company', 'placementCompany', 'branch', 'department', 'position', 'payrollComponents'])
            ->get()
            ->map(function ($a) {
                $schemaLabels = [
                    'MONTHLY_PAID_MONTHLY'  => 'Bulanan dibayar Bulanan',
                    'DAILY_PAID_MONTHLY'    => 'Harian dibayar Bulanan',
                    'DAILY_PAID_WEEKLY'     => 'Harian dibayar Mingguan',
                    'DAILY_PAID_DAILY'      => 'Harian dibayar per Hari',
                ];
                return [
                    'id'                     => $a->id,
                    'is_primary'             => $a->is_primary,
                    'company_id'             => $a->company_id,
                    'company_name'           => $a->company?->name ?? '-',
                    'placement_company_id'   => $a->placement_company_id,
                    'placement_company_name' => $a->placementCompany?->name ?? null,
                    'branch_name'            => $a->branch?->name ?? '-',
                    'department_name'        => $a->department?->name ?? '-',
                    'position_name'          => $a->position?->name ?? '-',
                    'employment_status'      => $a->employment_status,
                    'salary_cycle_type'      => $a->salary_cycle_type,
                    'salary_cycle_label'     => $schemaLabels[$a->salary_cycle_type] ?? $a->salary_cycle_type ?? 'Belum diset',
                    'components_count'       => $a->payrollComponents->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $assignments,
        ]);
    }

    /**
     * Update konfigurasi akses web karyawan.
     * Hanya bisa diakses oleh Super Admin / Admin.
     */
    public function updateWebAccess(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'web_access_enabled'   => 'required|boolean',
            'web_role'             => 'nullable|in:super_admin,staff_hr,spv',
            'managed_division_ids' => 'nullable|array',
            'managed_division_ids.*' => 'integer|exists:divisions,id',
        ]);

        // Jika akses dimatikan, hapus juga role-nya
        if (!$request->boolean('web_access_enabled')) {
            $employee->update([
                'web_access_enabled'   => false,
                'web_role'             => null,
                'managed_division_ids' => null,
            ]);
        } else {
            $employee->update([
                'web_access_enabled'   => true,
                'web_role'             => $request->web_role,
                'managed_division_ids' => ($request->web_role === 'spv')
                    ? ($request->managed_division_ids ?? [])
                    : null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Akses web karyawan berhasil diperbarui',
            'data'    => [
                'id'                   => $employee->id,
                'name'                 => $employee->name,
                'web_access_enabled'   => $employee->web_access_enabled,
                'web_role'             => $employee->web_role,
                'managed_division_ids' => $employee->managed_division_ids,
            ],
        ]);
    }

}
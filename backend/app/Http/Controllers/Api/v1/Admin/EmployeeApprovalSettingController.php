<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeApprovalDelegation;
use App\Models\EmployeeApprovalLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeApprovalSettingController extends Controller
{
    /**
     * Ambil seluruh approval lines untuk satu karyawan (dikelompokkan per modul).
     */
    public function lines(Employee $employee): JsonResponse
    {
        $lines = EmployeeApprovalLine::where('employee_id', $employee->id)
            ->with('approver:id,name,employee_id')
            ->orderBy('module')
            ->orderBy('step_order')
            ->get();

        // Kelompokkan per module
        $grouped = $lines->groupBy('module')->map(function ($steps) {
            return $steps->values();
        });

        return response()->json([
            'success' => true,
            'data'    => $grouped,
        ]);
    }

    /**
     * Simpan/replace seluruh approval lines untuk satu karyawan dalam satu modul.
     * Body: { module: 'leave', steps: [{approver_id, is_optional}] }
     */
    public function saveLines(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'module'              => ['required', 'string', Rule::in([
                'leave', 'sick_leave', 'permission', 'reimbursement', 'late', 'outside_location', 'permit', 'overtime', 'loan_request', 'workday_swap'
            ])],
            'steps'               => ['required', 'array', 'min:1'],
            'steps.*.approver_id' => ['required', 'integer', 'exists:employees,id'],
            'steps.*.is_optional' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $employee) {
            // Hapus semua step lama untuk modul ini (gunakan forceDelete agar unique constraint tidak bentrok dengan record lama)
            EmployeeApprovalLine::where('employee_id', $employee->id)
                ->where('module', $data['module'])
                ->forceDelete();

            // Simpan step baru berurutan
            foreach ($data['steps'] as $index => $step) {
                EmployeeApprovalLine::create([
                    'employee_id' => $employee->id,
                    'module'      => $data['module'],
                    'step_order'  => $index + 1,
                    'approver_id' => $step['approver_id'],
                    'is_optional' => $step['is_optional'] ?? false,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Alur persetujuan berhasil disimpan.',
        ]);
    }

    /**
     * Hapus seluruh approval lines untuk satu modul.
     */
    public function deleteLines(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'module' => ['required', 'string'],
        ]);

        EmployeeApprovalLine::where('employee_id', $employee->id)
            ->where('module', $data['module'])
            ->forceDelete();

        return response()->json(['success' => true, 'message' => 'Alur persetujuan dihapus.']);
    }

    // =============================================
    // DELEGASI
    // =============================================

    /**
     * List semua delegasi untuk karyawan ini (sebagai delegator).
     */
    public function delegations(Employee $employee): JsonResponse
    {
        $delegations = EmployeeApprovalDelegation::where('delegator_id', $employee->id)
            ->with('delegate:id,name,employee_id')
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $delegations,
        ]);
    }

    /**
     * Tambah delegasi baru.
     */
    public function storeDelegation(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'delegate_id' => ['required', 'integer', 'exists:employees,id', Rule::notIn([$employee->id])],
            'module'      => ['nullable', 'string'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'reason'      => ['nullable', 'string', 'max:1000'],
        ]);

        $delegation = EmployeeApprovalDelegation::create([
            'delegator_id' => $employee->id,
            ...$data,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delegasi berhasil ditambahkan.',
            'data'    => $delegation->load('delegate:id,name,employee_id'),
        ], 201);
    }

    /**
     * Update delegasi.
     */
    public function updateDelegation(Request $request, Employee $employee, EmployeeApprovalDelegation $delegation): JsonResponse
    {
        abort_if($delegation->delegator_id !== $employee->id, 403);

        $data = $request->validate([
            'delegate_id' => ['sometimes', 'integer', 'exists:employees,id', Rule::notIn([$employee->id])],
            'module'      => ['nullable', 'string'],
            'start_date'  => ['sometimes', 'date'],
            'end_date'    => ['sometimes', 'date', 'after_or_equal:start_date'],
            'reason'      => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $delegation->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Delegasi diperbarui.',
            'data'    => $delegation->fresh()->load('delegate:id,name,employee_id'),
        ]);
    }

    /**
     * Hapus delegasi.
     */
    public function destroyDelegation(Employee $employee, EmployeeApprovalDelegation $delegation): JsonResponse
    {
        abort_if($delegation->delegator_id !== $employee->id, 403);
        $delegation->delete();

        return response()->json(['success' => true, 'message' => 'Delegasi dihapus.']);
    }
}

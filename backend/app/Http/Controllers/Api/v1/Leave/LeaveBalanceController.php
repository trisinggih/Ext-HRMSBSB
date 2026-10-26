<?php

namespace App\Http\Controllers\Api\v1\Leave;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LeaveBalance::with(['employee:id,employee_id,name', 'leaveType']);

        if ($request->filled('employee_id')) {
            $employeeId = $request->employee_id;
            $year = $request->integer('year', now()->year);

            // Inisialisasi otomatis saldo cuti untuk setiap tipe cuti aktif jika belum ada di tahun ini
            $activeTypes = \App\Models\LeaveType::where('is_active', true)->get();
            foreach ($activeTypes as $type) {
                $defaultQuota = (int) ($type->default_days ?? 0);
                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                    ],
                    [
                        'quota' => $defaultQuota,
                        'used' => 0,
                        'carried_forward' => 0,
                        'expired' => 0,
                        'remaining' => $defaultQuota,
                    ]
                );
            }

            // Sinkronisasi actual used dari leave requests yang approved
            $balances = LeaveBalance::where('employee_id', $employeeId)->where('year', $year)->get();
            foreach ($balances as $b) {
                $actualUsed = (int) round((float) \App\Models\LeaveRequest::where('employee_id', $employeeId)
                    ->where('leave_type_id', $b->leave_type_id)
                    ->whereYear('start_date', $year)
                    ->where('status', 'approved')
                    ->sum('total_days'));

                if ($actualUsed > 0 && (int) $b->used != $actualUsed) {
                    $b->used = $actualUsed;
                    $b->remaining = max(0, ((int) $b->quota + (int) $b->carried_forward) - $actualUsed - (int) $b->expired);
                    $b->save();
                }
            }

            $query->where('employee_id', $employeeId);
        }
        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $list = $query->orderBy('year', 'desc')->orderBy('leave_type_id', 'asc')->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Daftar Saldo Cuti',
            'data' => $list,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer',
            'quota' => 'required|integer|min:0',
            'used' => 'nullable|integer|min:0',
            'carried_forward' => 'nullable|integer|min:0',
            'expired' => 'nullable|integer|min:0',
        ]);

        $quota = (int) $validated['quota'];

        // Check if balance already exists for this year and type
        $existing = LeaveBalance::where('employee_id', $validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $validated['year'])
            ->first();

        if ($existing) {
            $used = isset($validated['used']) ? (int)$validated['used'] : (int)$existing->used;
            $carried = isset($validated['carried_forward']) ? (int)$validated['carried_forward'] : (int)$existing->carried_forward;
            $expired = isset($validated['expired']) ? (int)$validated['expired'] : (int)$existing->expired;
            $remaining = max(0, ($quota + $carried) - $used - $expired);

            $existing->update([
                'quota' => $quota,
                'used' => $used,
                'carried_forward' => $carried,
                'expired' => $expired,
                'remaining' => $remaining,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Saldo cuti berhasil diperbarui',
                'data' => $existing->load(['employee', 'leaveType']),
            ]);
        }

        $used = (int) ($validated['used'] ?? 0);
        $carried = (int) ($validated['carried_forward'] ?? 0);
        $expired = (int) ($validated['expired'] ?? 0);
        $remaining = max(0, ($quota + $carried) - $used - $expired);

        $balance = LeaveBalance::create([
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'year' => $validated['year'],
            'quota' => $quota,
            'used' => $used,
            'carried_forward' => $carried,
            'expired' => $expired,
            'remaining' => $remaining,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Saldo cuti berhasil disimpan',
            'data' => $balance->load(['employee', 'leaveType']),
        ]);
    }

    public function show(LeaveBalance $leaveBalance): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $leaveBalance->load(['employee', 'leaveType']),
        ]);
    }

    public function update(Request $request, LeaveBalance $leaveBalance): JsonResponse
    {
        $validated = $request->validate([
            'quota' => 'required|integer|min:0',
            'used' => 'nullable|integer|min:0',
            'carried_forward' => 'nullable|integer|min:0',
            'expired' => 'nullable|integer|min:0',
        ]);

        $used = isset($validated['used']) ? (int)$validated['used'] : (int)$leaveBalance->used;
        $carried = isset($validated['carried_forward']) ? (int)$validated['carried_forward'] : (int)$leaveBalance->carried_forward;
        $expired = isset($validated['expired']) ? (int)$validated['expired'] : (int)$leaveBalance->expired;
        $quota = (int)$validated['quota'];

        $remaining = max(0, ($quota + $carried) - $used - $expired);

        $leaveBalance->update([
            'quota' => $quota,
            'used' => $used,
            'carried_forward' => $carried,
            'expired' => $expired,
            'remaining' => $remaining,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Saldo cuti berhasil diperbarui',
            'data' => $leaveBalance->load(['employee', 'leaveType']),
        ]);
    }

    public function destroy(LeaveBalance $leaveBalance): JsonResponse
    {
        $leaveBalance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Saldo cuti dihapus',
        ]);
    }
}

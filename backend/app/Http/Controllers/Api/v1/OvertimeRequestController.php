<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OvertimeRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OvertimeRequest::with(['employee', 'shift']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $perPage = $request->input('per_page', 15);
        $requests = $query->latest()->paginate($perPage);

        // Fetch corresponding attendance manually since there's no direct relation
        $requests->getCollection()->transform(function ($req) {
            $attendance = \App\Models\Attendance::where('employee_id', $req->employee_id)
                ->where('date', $req->date)
                ->select('overtime_clock_in_at', 'overtime_clock_out_at', 'overtime_minutes')
                ->first();
            $req->attendance = $attendance;
            return $req;
        });

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ]
        ]);
    }

    public function approve(OvertimeRequest $overtimeRequest): JsonResponse
    {
        $overtimeRequest->update([
            'status' => 'approved',
            'hr_approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil disetujui',
            'data' => $overtimeRequest->load(['employee', 'shift']),
        ]);
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $overtimeRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur ditolak',
            'data' => $overtimeRequest->load(['employee', 'shift']),
        ]);
    }
}

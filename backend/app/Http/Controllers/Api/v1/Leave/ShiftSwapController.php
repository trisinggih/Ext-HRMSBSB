<?php

namespace App\Http\Controllers\Api\v1\Leave;

use App\Http\Controllers\Controller;
use App\Models\ShiftSwapRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftSwapController extends Controller
{
    public function __construct(private \App\Services\ApprovalEngineService $engine)
    {
    }

    /** POST /shift-swap/request */
    public function store(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'target_employee_id' => 'required|integer|exists:employees,id|different:user_id',
            'shift_id' => 'required|integer|exists:shifts,id',
            'swap_date' => 'required|date',
            'reason' => 'required|string|max:1000',
        ]);

        $emp = $request->user();

        // cek konflik: employee tidak boleh punya shift swap pending di tanggal sama
        $exists = ShiftSwapRequest::where('employee_id', $emp->id)
            ->whereDate('swap_date', $valid['swap_date'])
            ->whereIn('status', ['pending', 'target_confirmed', 'supervisor_approved'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false, 'message' => 'Sudah ada permohonan tukar shift di tanggal tersebut', 'data' => null,
            ], 422);
        }

        $data = [
            'employee_id' => $emp->id,
            'target_employee_id' => $valid['target_employee_id'],
            'shift_id' => $valid['shift_id'],
            'swap_date' => $valid['swap_date'],
            'reason' => $valid['reason'],
            'status' => 'pending',
        ];

        $swap = ShiftSwapRequest::create($data);
        $this->engine->dispatch('shift_swap', $swap->id, $emp->id, $valid);

        return response()->json([
            'success' => true, 'message' => 'Permohonan tukar shift dikirim', 'data' => $swap->load('shift'),
        ], 201);
    }

    /** POST /shift-swap/{id}/confirm — target konfirmasi */
    public function confirm(Request $request, ShiftSwapRequest $swap): JsonResponse
    {
        if ($request->user()->id !== $swap->target_employee_id) {
            return response()->json(['success' => false, 'message' => 'Bukan target swap', 'data' => null], 403);
        }
        if ($swap->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Status tidak valid untuk konfirmasi', 'data' => null], 422);
        }
        $swap->update(['status' => 'target_confirmed', 'target_confirmed_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Target menyetujui swap', 'data' => $swap]);
    }

    /** GET /shift-swap/request */
    public function index(Request $request): JsonResponse
    {
        $requests = ShiftSwapRequest::with('shift', 'employee')
            ->where(fn ($q) => $q->where('employee_id', $request->user()->id)
                ->orWhere('target_employee_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true, 'message' => 'Daftar tukar shift', 'data' => $requests,
        ]);
    }
}

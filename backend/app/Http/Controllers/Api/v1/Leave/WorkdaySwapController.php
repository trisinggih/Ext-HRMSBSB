<?php

namespace App\Http\Controllers\Api\v1\Leave;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SickLeaveRequest;
use App\Models\WorkdaySwapRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkdaySwapController extends Controller
{
    public function __construct(private \App\Services\ApprovalEngineService $engine)
    {
    }

    /** POST /workday-swap/request */
    public function store(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'work_date' => 'required|date',
            'swap_to_date' => 'required|date|different:work_date',
            'reason' => 'required|string|max:1000',
        ]);

        $emp = $request->user();

        // Validasi minimal H-1 (mulai dari besok)
        $minAllowedDate = now('Asia/Jakarta')->startOfDay()->addDay();
        if (\Carbon\Carbon::parse($valid['work_date'])->startOfDay()->lt($minAllowedDate) || \Carbon\Carbon::parse($valid['swap_to_date'])->startOfDay()->lt($minAllowedDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan tukar hari kerja minimal dilakukan H-1.',
                'data' => null,
            ], 422);
        }

        // validasi konflik di work_date: ada attendance, cuti, atau cuti sakit?
        $conflict = Attendance::where('employee_id', $emp->id)->whereDate('date', $valid['work_date'])->exists()
            || LeaveRequest::where('employee_id', $emp->id)->where('start_date', '<=', $valid['work_date'])->where('end_date', '>=', $valid['work_date'])->whereIn('status', ['approved', 'pending'])->exists()
            || SickLeaveRequest::where('employee_id', $emp->id)->where('start_date', '<=', $valid['work_date'])->where('end_date', '>=', $valid['work_date'])->whereIn('status', ['approved', 'pending'])->exists();

        if ($conflict) {
            return response()->json([
                'success' => false, 'message' => 'Terdapat kehadiran/cuti/sakit di tanggal tersebut, tidak bisa tukar', 'data' => null,
            ], 422);
        }

        $data = [
            'employee_id' => $emp->id,
            'work_date' => $valid['work_date'],
            'swap_to_date' => $valid['swap_to_date'],
            'reason' => $valid['reason'],
            'status' => 'pending',
        ];

        $swap = WorkdaySwapRequest::create($data);
        $this->engine->dispatch('workday_swap', $swap->id, $emp->id, $valid);

        \App\Services\ActivityLogService::log(
            $emp, 
            'create_workday_swap', 
            "{$emp->name} mengajukan tukar hari kerja dari " . \Carbon\Carbon::parse($valid['work_date'])->translatedFormat('d F Y') . " ke " . \Carbon\Carbon::parse($valid['swap_to_date'])->translatedFormat('d F Y'), 
            'Pengajuan Tukar Hari Kerja', 
            $swap
        );

        return response()->json([
            'success' => true, 'message' => 'Permohonan tukar hari kerja dikirim', 'data' => $swap,
        ], 201);
    }

    /** GET /workday-swap/request */
    public function index(Request $request): JsonResponse
    {
        $requests = WorkdaySwapRequest::where('employee_id', $request->user()->id)
            ->with('approval')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true, 'message' => 'Daftar tukar hari kerja', 'data' => $requests,
        ]);
    }
}

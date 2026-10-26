<?php

namespace App\Http\Controllers\Api\v1\Leave;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Services\ApprovalEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function __construct(private ApprovalEngineService $engine)
    {
    }

    private function employee(Request $request): Employee
    {
        return $request->user();
    }

    /** GET /leave/balance — saldo cuti tahun berjalan */
    public function balance(Request $request): JsonResponse
    {
        $emp = $this->employee($request);
        $year = $request->integer('year', now()->year);

        $activeTypes = LeaveType::where('is_active', true)->get();
        $balances = LeaveBalance::where('employee_id', $emp->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        $result = $activeTypes->map(function ($type) use ($balances, $year) {
            $b = $balances->get($type->id);

            return [
                'leave_type_id'   => $type->id,
                'leave_type_name' => $type->name,
                'leave_type_code' => $type->code,
                'quota'           => $b ? (float) $b->quota     : 0.0,
                'used'            => $b ? (float) $b->used      : 0.0,
                'remaining'       => $b ? (float) $b->remaining : 0.0,
                'year'            => (int) $year,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Saldo cuti',
            'data'    => $result,
        ]);
    }

    /** POST /leave/request — ajukan cuti */
    public function store(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $emp = $this->employee($request);
        $type = LeaveType::findOrFail($valid['leave_type_id']);
        if (!$type->is_active) {
            throw ValidationException::withMessages(['leave_type_id' => 'Tipe cuti tidak aktif']);
        }

        $start = Carbon::parse($valid['start_date'])->startOfDay();
        $end = Carbon::parse($valid['end_date'])->startOfDay();

        // Validasi minimal H-1 (mulai dari besok)
        $minAllowedDate = now('Asia/Jakarta')->startOfDay()->addDay();
        if ($start->lt($minAllowedDate)) {
            throw ValidationException::withMessages([
                'start_date' => ['Pengajuan cuti minimal dilakukan H-1 (mulai dari besok).'],
            ]);
        }

        $totalDays = $start->diffInDays($end) + 1;

        $balance = LeaveBalance::where('employee_id', $emp->id)
            ->where('leave_type_id', $type->id)
            ->where('year', $start->year)
            ->first();

        if (!$balance || (float) $balance->remaining <= 0) {
            $sisa = $balance ? (float) $balance->remaining : 0;
            throw ValidationException::withMessages([
                'leave_type_id' => "Saldo cuti {$type->name} tidak mencukupi. Sisa saldo: {$sisa} hari.",
            ]);
        }

        $availableRemaining = (float) $balance->remaining;
        if ($totalDays > $availableRemaining) {
            throw ValidationException::withMessages([
                'end_date' => "Saldo tidak cukup. Tersisa {$availableRemaining} hari, butuh {$totalDays} hari.",
            ]);
        }

        $data = [
            'employee_id' => $emp->id,
            'leave_type_id' => $type->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => $totalDays,
            'reason' => $valid['reason'],
            'status' => 'pending',
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('attachments/leave', 'public');
        }

        $leaveRequest = DB::transaction(function () use ($data, $balance, $totalDays) {
            $lr = LeaveRequest::create($data);
            // hold saldo
            $balance->decrement('remaining', $totalDays);
            $balance->increment('used', $totalDays);
            $this->engine->dispatch('leave', $lr->id, auth()->id(), [
                'leave_type' => LeaveType::find($lr->leave_type_id)->name,
                'start_date' => $lr->start_date,
                'end_date' => $lr->end_date,
                'total_days' => $lr->total_days,
                'reason' => $lr->reason,
                'attachment' => $lr->attachment,
            ]);
            return $lr;
        });

        \App\Services\ActivityLogService::log(
            $emp, 
            'create_leave', 
            "{$emp->name} mengajukan cuti pada tanggal " . \Carbon\Carbon::parse($leaveRequest->start_date)->translatedFormat('d F Y') . " sampai " . \Carbon\Carbon::parse($leaveRequest->end_date)->translatedFormat('d F Y'), 
            'Pengajuan Cuti', 
            $leaveRequest
        );

        return response()->json([
            'success' => true, 'message' => 'Permohonan cuti dikirim', 'data' => $leaveRequest->load('leaveType'),
        ], 201);
    }

    /** GET /leave/request — daftar cuti milik employee */
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employee($request);
        $requests = LeaveRequest::with(['leaveType', 'approval.workflow.steps.approverEmployee', 'approval.logs'])
            ->where('employee_id', $emp->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true, 'message' => 'Daftar permohonan cuti', 'data' => $requests,
        ]);
    }

    /** GET /leave-requests/admin — daftar SEMUA cuti untuk admin */
    public function adminIndex(Request $request): JsonResponse
    {
        $requests = LeaveRequest::with(['leaveType', 'employee'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true, 'message' => 'Daftar permohonan cuti (Admin)', 'data' => $requests,
        ]);
    }
}

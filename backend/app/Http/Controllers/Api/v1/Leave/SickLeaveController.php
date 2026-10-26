<?php

namespace App\Http\Controllers\Api\v1\Leave;

use App\Http\Controllers\Controller;
use App\Models\SickLeaveRequest;
use App\Services\ApprovalEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SickLeaveController extends Controller
{
    public function __construct(private ApprovalEngineService $engine)
    {
    }

    /** POST /sick-leave/request */
    public function store(Request $request): JsonResponse
    {
        if (!$request->filled('end_date') && $request->filled('start_date')) {
            $request->merge(['end_date' => $request->input('start_date')]);
        }

        $valid = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'medical_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:10240',
        ]);

        $valid['end_date'] = $valid['end_date'] ?? $valid['start_date'];

        $now = Carbon::now('Asia/Jakarta');
        $deadline = $now->copy()->setTime(10, 0, 0);
        $today = $now->toDateString();
        $startDate = Carbon::parse($valid['start_date'])->toDateString();

        if ($startDate < $today) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal mulai sakit tidak boleh sebelum hari ini.',
                'errors' => [
                    'start_date' => ['Tanggal mulai sakit tidak boleh sebelum hari ini.'],
                ],
            ], 422);
        }

        if ($startDate == $today && $now->gt($deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan izin sakit maksimal diajukan sebelum pukul 10:00 WIB.',
                'errors' => [
                    'start_date' => ['Pengajuan izin sakit maksimal diajukan sebelum pukul 10:00 WIB.'],
                ],
            ], 422);
        }

        $emp = $request->user();
        $totalDays = Carbon::parse($valid['start_date'])->diffInDays(Carbon::parse($valid['end_date'])) + 1;

        $data = [
            'employee_id' => $emp->id,
            'start_date' => $valid['start_date'],
            'end_date' => $valid['end_date'],
            'total_days' => $totalDays,
            'reason' => $valid['reason'],
            'status' => 'pending',
        ];

        if ($request->hasFile('medical_certificate')) {
            $data['medical_certificate'] = $request->file('medical_certificate')->store('attachments/sick', 'public');
        }

        $sick = SickLeaveRequest::create($data);

        $payload = $valid;
        if (isset($data['medical_certificate'])) {
            $payload['medical_certificate'] = $data['medical_certificate'];
        } else {
            unset($payload['medical_certificate']);
        }

        $this->engine->dispatch('sick_leave', $sick->id, $emp->id, $payload);

        return response()->json([
            'success' => true, 'message' => 'Permohonan cuti sakit dikirim', 'data' => $sick,
        ], 201);
    }

    /** GET /sick-leave/request — daftar cuti sakit milik employee */
    public function index(Request $request): JsonResponse
    {
        $requests = SickLeaveRequest::with(['approval.workflow.steps.approverEmployee', 'approval.logs'])
            ->where('employee_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true, 'message' => 'Daftar cuti sakit', 'data' => $requests,
        ]);
    }
}

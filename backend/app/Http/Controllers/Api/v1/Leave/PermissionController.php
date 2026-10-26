<?php

namespace App\Http\Controllers\Api\v1\Leave;

use App\Http\Controllers\Controller;
use App\Models\PermissionRequest;
use App\Services\ApprovalEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    public function __construct(private ApprovalEngineService $engine)
    {
    }

    /** POST /permission/request */
    public function store(Request $request): JsonResponse
    {
        $startDateInput = $request->input('start_date') ?: $request->input('date');
        $endDateInput = $request->input('end_date') ?: $request->input('to_date') ?: $startDateInput;

        $request->merge([
            'date' => $startDateInput,
            'start_date' => $startDateInput,
            'end_date' => $endDateInput,
        ]);

        $valid = $request->validate([
            'type' => 'required|in:personal,business,family,other,izin,cuti,kepentingan,urgent,out_of_office,izin_keluar',
            'date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $isExitPermit = in_array($valid['type'], ['out_of_office', 'izin_keluar']);
        $startDate = Carbon::parse($valid['start_date'])->startOfDay();
        $endDate = Carbon::parse($valid['end_date'])->startOfDay();
        $today = now('Asia/Jakarta')->startOfDay();

        if ($isExitPermit) {
            // Izin Keluar hanya untuk hari ini (1 hari) dengan jam keluar & jam kembali
            if (!$startDate->eq($today)) {
                throw ValidationException::withMessages([
                    'date' => ['Pengajuan izin keluar hanya dapat diajukan untuk hari ini.'],
                ]);
            }
            if (!$endDate->eq($today)) {
                throw ValidationException::withMessages([
                    'end_date' => ['Pengajuan izin keluar hanya berlaku untuk 1 hari (hari ini).'],
                ]);
            }
            if (empty($valid['start_time']) || empty($valid['end_time'])) {
                throw ValidationException::withMessages([
                    'start_time' => ['Jam keluar dan jam kembali wajib diisi untuk izin keluar.'],
                ]);
            }
            if ($valid['end_time'] <= $valid['start_time']) {
                throw ValidationException::withMessages([
                    'end_time' => ['Jam selesai harus setelah jam mulai.'],
                ]);
            }
        } else {
            // Izin Pribadi & Izin Dinas Luar Kota: minimal hari ini dan bisa beberapa hari (rentang tanggal)
            if ($startDate->lt($today)) {
                throw ValidationException::withMessages([
                    'start_date' => ['Pengajuan izin tidak boleh sebelum hari ini.'],
                ]);
            }
            if ($endDate->lt($startDate)) {
                throw ValidationException::withMessages([
                    'end_date' => ['Tanggal selesai izin tidak boleh sebelum tanggal mulai.'],
                ]);
            }
            if (!empty($valid['start_time']) && !empty($valid['end_time']) && $valid['end_time'] <= $valid['start_time']) {
                throw ValidationException::withMessages([
                    'end_time' => ['Jam selesai harus setelah jam mulai.'],
                ]);
            }
        }

        $emp = $request->user();

        // Cek jika sudah memiliki pengajuan izin aktif pada rentang tanggal yang sama
        $hasExisting = PermissionRequest::where('employee_id', $emp->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($valid) {
                $q->where(function ($sub) use ($valid) {
                    $sub->whereRaw('COALESCE(end_date, date) >= ?', [$valid['start_date']])
                        ->where('date', '<=', $valid['end_date']);
                });
            })
            ->exists();

        if ($hasExisting) {
            throw ValidationException::withMessages([
                'date' => ['Anda sudah memiliki pengajuan izin yang aktif pada rentang tanggal ini.'],
            ]);
        }

        $data = [
            'employee_id' => $emp->id,
            'type' => $valid['type'],
            'date' => $valid['start_date'],
            'end_date' => $valid['end_date'],
            'start_time' => $valid['start_time'] ?: '08:00',
            'end_time' => $valid['end_time'] ?: '17:00',
            'reason' => $valid['reason'],
            'status' => 'pending',
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('attachments/permission', 'public');
        }

        $perm = PermissionRequest::create($data);
        $this->engine->dispatch('permission', $perm->id, $emp->id, array_merge($valid, [
            'date' => $valid['start_date'],
            'start_date' => $valid['start_date'],
            'end_date' => $valid['end_date'],
            'to_date' => $valid['end_date'],
        ]));

        return response()->json([
            'success' => true, 'message' => 'Permohonan izin dikirim', 'data' => $perm,
        ], 201);
    }

    /** GET /permission/request — daftar izin milik employee */
    public function index(Request $request): JsonResponse
    {
        $requests = PermissionRequest::with(['approval.workflow.steps.approverEmployee', 'approval.logs'])
            ->where('employee_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true, 'message' => 'Daftar permohonan izin', 'data' => $requests,
        ]);
    }
}

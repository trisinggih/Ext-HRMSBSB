<?php

namespace App\Http\Controllers\Api\v1\Leave;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeApprovalLine;
use App\Models\EmployeeShift;
use App\Models\LateRequest;
use App\Models\Notification;
use App\Services\FCMService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LateController extends Controller
{
    /** POST /late/request — laporan keterlambatan (cukup jadi info/notifikasi ke atasan tanpa alur persetujuan) */
    public function store(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'date' => 'required|date',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $emp = $request->user();
        $date = $valid['date'];

        $attendance = Attendance::where('employee_id', $emp->id)
            ->where(function($q) use ($date) {
                $q->whereDate('date', $date)
                  ->orWhereDate('clock_in_at', $date);
            })
            ->latest('id')
            ->first();

        $shift = EmployeeShift::with('shift')
            ->where('employee_id', $emp->id)
            ->whereDate('effective_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
            ->orderByDesc('effective_date')
            ->first();

        $scheduled = $shift?->shift?->clock_in ?? '08:00';
        $actual = $attendance?->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '08:00';
        // late_minutes: selisih menit actual vs scheduled (jika actual lebih lambat)
        $lateMinutes = 0;
        if ($attendance?->clock_in_at) {
            $lateMinutes = max(0, Carbon::parse($attendance->clock_in_at)->diffInMinutes(Carbon::parse($scheduled)));
        }

        $data = [
            'employee_id' => $emp->id,
            'attendance_id' => $attendance?->id,
            'date' => $date,
            'scheduled_clock_in' => $scheduled,
            'actual_clock_in' => $attendance?->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : null,
            'late_minutes' => $lateMinutes,
            'reason' => $valid['reason'],
            'status' => 'approved', // Persetujuan dihilangkan, langsung disetujui / diakui
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('attachments/late', 'public');
        }

        $late = LateRequest::create($data);

        // Update attendance record with late reason as notes
        if ($attendance) {
            $attendance->notes = $valid['reason'];
            $attendance->save();
        }

        // Kirim info / notifikasi ke atasan (in-app dan push notification)
        $this->notifySupervisors($emp, $late, $date, $lateMinutes, $valid['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Laporan keterlambatan berhasil dikirim dan diinfokan ke atasan',
            'data' => $late,
        ], 201);
    }

    /** GET /late/request */
    public function index(Request $request): JsonResponse
    {
        $requests = LateRequest::with(['attendance'])
            ->where('employee_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar keterlambatan',
            'data' => $requests,
        ]);
    }

    /**
     * Dapatkan daftar ID atasan / supervisor yang perlu diberi notifikasi.
     */
    protected function getSupervisorIds(Employee $emp): array
    {
        $supervisorIds = [];

        $emp->loadMissing(['primaryAssignment.department', 'assignments.department']);

        // 1. Dari supervisor_id di primary assignment
        if ($emp->primaryAssignment?->supervisor_id) {
            $supervisorIds[] = $emp->primaryAssignment->supervisor_id;
        }

        // 2. Dari assignments lain yang memiliki supervisor_id
        if ($emp->assignments) {
            foreach ($emp->assignments as $assignment) {
                if ($assignment->supervisor_id && !in_array($assignment->supervisor_id, $supervisorIds)) {
                    $supervisorIds[] = $assignment->supervisor_id;
                }
            }
        }

        // 3. Fallback ke department manager jika supervisor belum ditentukan
        if (empty($supervisorIds) && $emp->primaryAssignment?->department?->manager_id) {
            $supervisorIds[] = $emp->primaryAssignment->department->manager_id;
        }

        // 4. Fallback ke approval lines (jika ada konfigurasi)
        $approvalLines = EmployeeApprovalLine::where('employee_id', $emp->id)
            ->pluck('approver_id')
            ->filter()
            ->toArray();
        foreach ($approvalLines as $approverId) {
            if (!in_array($approverId, $supervisorIds)) {
                $supervisorIds[] = $approverId;
            }
        }

        // Pastikan tidak mengirim ke diri sendiri dan unik
        return array_values(array_filter(array_unique($supervisorIds), fn ($id) => (int)$id !== (int)$emp->id));
    }

    /**
     * Kirim notifikasi in-app dan FCM ke atasan mengenai keterlambatan.
     */
    protected function notifySupervisors(Employee $emp, LateRequest $late, string $date, int $lateMinutes, string $reason): void
    {
        $supervisorIds = $this->getSupervisorIds($emp);
        if (empty($supervisorIds)) {
            return;
        }

        $formattedDate = Carbon::parse($date)->translatedFormat('d F Y');
        $notifTitle = 'Info Keterlambatan: ' . $emp->name;
        $lateDuration = $lateMinutes > 0 ? " ({$lateMinutes} menit)" : '';
        $notifBody = "{$emp->name} mengonfirmasi keterlambatan pada {$formattedDate}{$lateDuration}. Alasan: {$reason}";

        $fcm = new FCMService();

        foreach ($supervisorIds as $supervisorId) {
            // 1. Simpan in-app notification
            Notification::create([
                'employee_id' => $supervisorId,
                'type' => 'late',
                'title' => $notifTitle,
                'body' => $notifBody,
                'data' => [
                    'type' => 'late_info',
                    'module' => 'late',
                    'late_request_id' => $late->id,
                    'employee_id' => $emp->id,
                    'employee_name' => $emp->name,
                    'date' => $date,
                    'late_minutes' => $lateMinutes,
                    'reason' => $reason,
                ],
                'is_read' => false,
            ]);

            // 2. Kirim push notification ke device supervisor jika ada fcm_token
            $supervisor = Employee::find($supervisorId);
            if ($supervisor && $supervisor->fcm_token) {
                try {
                    $fcm->sendToDevice(
                        $supervisor->fcm_token,
                        $notifTitle,
                        $notifBody,
                        [
                            'type' => 'late_info',
                            'module' => 'late',
                            'module_id' => (string) $late->id,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning("Gagal mengirim push notification keterlambatan ke supervisor {$supervisorId}: " . $e->getMessage());
                }
            }
        }
    }
}


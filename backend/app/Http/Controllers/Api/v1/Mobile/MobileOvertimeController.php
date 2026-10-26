<?php

namespace App\Http\Controllers\Api\v1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MobileOvertimeController extends Controller
{
    public function history(Request $request): JsonResponse
    {
        $employeeId = $request->user()->id;
        $history = OvertimeRequest::with(['employee', 'approval.logs', 'approval.workflow.steps.approverEmployee'])
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil riwayat lembur',
            'data' => $history
        ]);
    }

    
    public function requestOvertime(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => 'nullable',
            'date' => 'required|date',
            'reason' => 'required|string',
            'requested_start_time' => 'required|date_format:H:i',
            'requested_end_time' => 'required|date_format:H:i',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        $today = date('Y-m-d');
        if ($request->date < $today) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal pengajuan lembur tidak boleh kurang dari hari ini (' . date('d-m-Y') . ')',
            ], 422);
        }

        $employeeId = $request->user()->id;
        $shiftId = $request->shift_id;
        
        if (empty($shiftId)) {
            // Cari dari absen
            $attendance = \App\Models\Attendance::where('employee_id', $employeeId)
                ->where('date', $request->date)
                ->first();
            if ($attendance && $attendance->shift_id) {
                $shiftId = $attendance->shift_id;
            } else {
                // Cari dari jadwal shift
                $employeeShift = \App\Models\EmployeeShift::where('employee_id', $employeeId)
                    ->where('effective_date', '<=', $request->date)
                    ->where(function($query) use ($request) {
                        $query->where('end_date', '>=', $request->date)
                              ->orWhereNull('end_date');
                    })
                    ->where('is_active', true)
                    ->first();
                if ($employeeShift && $employeeShift->shift_id) {
                    $shiftId = $employeeShift->shift_id;
                }
            }
        }

        $shift = \App\Models\Shift::find($shiftId);

        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'Shift tidak ditemukan untuk tanggal tersebut'], 404);
        }

        $minStartTime = $shift->max_overtime_request_time ?: '20:00:00';
        if ($minStartTime > '20:00:00') {
            $minStartTime = '20:00:00';
        }
        $reqTimeStr = date('H:i:s', strtotime($request->requested_start_time));
        $minTimeStr = date('H:i:s', strtotime($minStartTime));

        if ($reqTimeStr < $minTimeStr) {
            return response()->json([
                'success' => false,
                'message' => 'Jam mulai lembur tidak boleh lebih awal dari ' . substr($minStartTime, 0, 5),
            ], 400);
        }

        $existing = OvertimeRequest::where('employee_id', $employeeId)
            ->where('date', $request->date)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengajukan lembur untuk tanggal ini',
            ], 400);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments/overtime', 'public');
        }

        $overtimeRequest = OvertimeRequest::create([
            'employee_id' => $employeeId,
            'shift_id' => $shift->id,
            'date' => $request->date,
            'reason' => $request->reason,
            'requested_start_time' => $request->requested_start_time,
            'requested_end_time' => $request->requested_end_time,
            'attachment' => $attachmentPath,
            'status' => 'pending',
        ]);

        $engine = app(\App\Services\ApprovalEngineService::class);
        $engine->dispatch('overtime', $overtimeRequest->id, $employeeId, [
            'date' => $request->date,
            'requested_start_time' => $request->requested_start_time,
            'requested_end_time' => $request->requested_end_time,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dibuat',
            'data' => $overtimeRequest,
        ], 201);
    }

    public function clockIn(Request $request): JsonResponse
    {
        $employeeId = $request->user()->id;
        $date = date('Y-m-d'); 
        
        $attendance = Attendance::where('employee_id', $employeeId)
            ->where(function($q) use ($date) {
                $q->where('date', $date)
                  ->orWhere('date', date('Y-m-d', strtotime('-1 day')));
            })
            ->latest('date')
            ->first();

        if (!$attendance) {
            return response()->json(['success' => false, 'message' => 'Data absensi reguler tidak ditemukan'], 404);
        }
        
        // Rule: Lembur harus setelah shift selesai, jadi pastikan absen reguler sudah di-clock out
        if (!$attendance->clock_out_at) {
            return response()->json(['success' => false, 'message' => 'Anda harus Clock-Out absen reguler terlebih dahulu sebelum memulai lembur'], 400);
        }

        // Rule: Lembur yang diajukan perlu di-approve dulu agar bisa clock in
        $overtimeRequest = OvertimeRequest::where('employee_id', $employeeId)
            ->where('date', $attendance->date)
            ->first();

        if (!$overtimeRequest) {
            return response()->json(['success' => false, 'message' => 'Tidak ada pengajuan lembur untuk hari ini'], 400);
        }

        if (!in_array($overtimeRequest->status, ['approved', 'in_progress'])) {
            return response()->json(['success' => false, 'message' => 'Pengajuan lembur Anda belum disetujui (Status: ' . $overtimeRequest->status . ')'], 400);
        }

        if ($attendance->overtime_clock_in_at || $overtimeRequest->status === 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Anda sudah melakukan clock in lembur'], 400);
        }

        $now = now();

        $attendance->update([
            'overtime_clock_in_at' => $now,
        ]);

        $overtimeRequest->update([
            'status' => 'in_progress',
            'actual_start_time' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil clock in lembur',
            'data' => $attendance,
        ]);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $request->validate([
            'report_text' => 'nullable|string',
            'face_photo' => 'nullable|string',
            'report_photos' => 'nullable|array',
            'report_photos.*' => 'file|image|max:5120',
        ]);

        $employeeId = $request->user()->id;
        $date = date('Y-m-d'); 
        
        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereNotNull('overtime_clock_in_at')
            ->whereNull('overtime_clock_out_at')
            ->latest('date')
            ->first();

        if (!$attendance) {
            return response()->json(['success' => false, 'message' => 'Anda belum clock in lembur atau sudah clock out'], 400);
        }

        $overtimeRequest = OvertimeRequest::where('employee_id', $employeeId)
            ->where('date', $attendance->date)
            ->first();

        $clockOutTime = now();
        $clockInTime = \Carbon\Carbon::parse($attendance->overtime_clock_in_at);
        $overtimeMinutes = abs((int) $clockOutTime->diffInMinutes($clockInTime));

        $photoPaths = [];
        if ($request->hasFile('report_photos')) {
            foreach ($request->file('report_photos') as $photo) {
                $photoPaths[] = $photo->store('overtime-reports', 'public');
            }
        }

        $facePhotoPath = null;
        if ($request->filled('face_photo')) {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $request->face_photo);
            $faceData = base64_decode($base64);
            $faceFileName = 'overtime-faces/' . uniqid() . '.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($faceFileName, $faceData);
            $facePhotoPath = $faceFileName;
        }

        $reportText = $request->report_text ?: 'Lembur selesai';

        $dataToUpdate = [
            'overtime_clock_out_at' => $clockOutTime,
            'overtime_minutes' => $overtimeMinutes,
            'overtime_report_text' => $reportText,
        ];

        if ($facePhotoPath) {
            $dataToUpdate['overtime_report_photo'] = $facePhotoPath;
        }

        $attendance->update($dataToUpdate);

        if ($overtimeRequest) {
            $overtimeRequest->update([
                'status' => 'done',
                'actual_end_time' => $clockOutTime,
                'result_note' => $reportText,
                'result_photos' => !empty($photoPaths) ? json_encode($photoPaths) : null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil clock out lembur',
            'data' => $attendance,
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $employeeId = $request->user()->id;
        $date = date('Y-m-d'); 
        
        $overtimeRequest = OvertimeRequest::where('employee_id', $employeeId)
            ->where(function($q) use ($date) {
                $q->where(function($todayQ) use ($date) {
                    $todayQ->where('date', $date)
                           ->whereIn('status', ['approved', 'in_progress']);
                })->orWhere(function($yesterdayQ) {
                    $yesterdayQ->where('date', date('Y-m-d', strtotime('-1 day')))
                               ->where('status', 'in_progress');
                });
            })
            ->latest('date')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $overtimeRequest,
        ]);
    }

    public function cancel(Request $request, $id): JsonResponse
    {
        $employeeId = $request->user()->id;
        $overtime = OvertimeRequest::where('employee_id', $employeeId)->where('id', $id)->first();
        
        if (!$overtime) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan lembur tidak ditemukan'
            ], 404);
        }
        if ($overtime->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan pending yang dapat dibatalkan'
            ], 400);
        }
        
        $request->validate(['reason' => 'required|string']);
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($overtime, $request) {
            $overtime->status = 'cancelled';
            $overtime->save();
            
            $approval = \App\Models\ApprovalRequest::where('module', 'overtime')->where('module_id', $overtime->id)->first();
            if ($approval) {
                $approval->status = 'cancelled';
                $approval->save();
                
                \App\Models\ApprovalLog::create([
                    'approval_request_id' => $approval->id,
                    'user_id' => $request->user()->id,
                    'action' => 'cancel',
                    'note' => $request->reason
                ]);
            }
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dibatalkan'
        ]);
    }
}

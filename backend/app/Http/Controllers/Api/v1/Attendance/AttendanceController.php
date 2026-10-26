<?php

namespace App\Http\Controllers\Api\v1\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BreakRequest;
use App\Http\Requests\Attendance\ClockInRequest;
use App\Http\Requests\Attendance\ClockOutRequest;
use App\Http\Requests\Attendance\HistoryRequest;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\EmployeeFace;
use App\Events\LiveLocationUpdated;
use App\Helpers\FaceMatchHelper;
use App\Models\EmployeeShift;
use App\Models\EmployeeShiftPattern;
use App\Models\Shift;
use App\Support\GeoLocation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * Cek apakah user diperbolehkan absen di luar radius dan informasi cabang yang diizinkan.
     */
    public function checkRadiusPermission(Request $request): JsonResponse
    {
        $employee = $request->user();
        $allowedBranches = ($employee instanceof \App\Models\Employee) ? $employee->getAllowedBranches() : collect();

        // Karyawan diperbolehkan absen di mana saja jika:
        // 1. can_clock_in_anywhere = true
        // 2. attendance_location_type = 'anywhere'
        // 3. Cabang penempatannya belum memiliki koordinat GPS (contoh: CV GASNOREM)
        $canClockInAnywhere = (bool)($employee->can_clock_in_anywhere ?? false)
            || (($employee->attendance_location_type ?? '') === 'anywhere')
            || $allowedBranches->isEmpty();

        return response()->json([
            'success' => true,
            'message' => 'Status izin radius berhasil diambil',
            'data' => [
                'can_clock_in_anywhere' => $canClockInAnywhere,
                'attendance_location_type' => $employee->attendance_location_type ?? 'assigned_branch',
                'allowed_branches' => $allowedBranches->map(fn($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'latitude' => $b->latitude,
                    'longitude' => $b->longitude,
                    'attendance_radius' => $b->attendance_radius,
                ]),
            ]
        ]);
    }

    /**
     * Ambil koordinat cabang yang diizinkan untuk karyawan saat ini (atau semua cabang bila unrestricted / fallback).
     */
    public function branchLocations(Request $request): JsonResponse
    {
        $employee = $request->user();
        if ($employee instanceof \App\Models\Employee) {
            $branches = $employee->getAllowedBranches();
            // Fallback: Jika karyawan tidak memiliki cabang berkoordinat (misal cabang belum disetel GPS),
            // kembalikan seluruh cabang aktif berkoordinat agar UI mobile map tidak error / kosong
            if ($branches->isEmpty()) {
                $branches = \App\Models\Branch::whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->where('latitude', '!=', '')
                    ->where('longitude', '!=', '')
                    ->get(['id', 'name', 'latitude', 'longitude', 'attendance_radius']);
            }
        } else {
            $branches = \App\Models\Branch::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '!=', '')
                ->where('longitude', '!=', '')
                ->get(['id', 'name', 'latitude', 'longitude', 'attendance_radius']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar lokasi cabang yang diizinkan untuk presensi',
            'data' => $branches
        ]);
    }
    /**
     * Ambil shift aktif untuk karyawan pada hari ini.
     */
    private function activeShift(int $employeeId): ?EmployeeShift
    {
        return EmployeeShift::query()
            ->with('shift')
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', now()->toDateString());
            })
            ->latest('id')
            ->first();
    }

    /**
     * Cari fallback shift jika karyawan masuk saat tidak ada shift aktif atau dijadwalkan libur/off.
     */
    private function resolveFallbackShift(\App\Models\Employee $employee): ?Shift
    {
        $dayOfWeek = now()->dayOfWeek;

        // 1. Cek pola shift mingguan karyawan untuk hari ini
        $pattern = EmployeeShiftPattern::with('shift')
            ->where('employee_id', $employee->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($pattern && $pattern->shift) {
            return $pattern->shift;
        }

        // Cek pola shift hari apa pun yang ada shift-nya milik karyawan
        $anyPattern = EmployeeShiftPattern::with('shift')
            ->where('employee_id', $employee->id)
            ->whereNotNull('shift_id')
            ->first();

        if ($anyPattern && $anyPattern->shift) {
            return $anyPattern->shift;
        }

        // 2. Cek riwayat penugasan shift karyawan sebelumnya yang memiliki shift valid
        $lastEmployeeShift = EmployeeShift::with('shift')
            ->where('employee_id', $employee->id)
            ->whereNotNull('shift_id')
            ->latest('effective_date')
            ->first();

        if ($lastEmployeeShift && $lastEmployeeShift->shift) {
            return $lastEmployeeShift->shift;
        }

        // 3. Cek shift perusahaan tempat karyawan ditugaskan (primary assignment)
        $primaryAssignment = $employee->primaryAssignment ?? $employee->assignments()->where('is_primary', true)->first();
        $companyId = $primaryAssignment?->company_id;

        if ($companyId) {
            $companyShift = Shift::where('company_id', $companyId)->first();
            if ($companyShift) {
                return $companyShift;
            }
        }

        // 4. Fallback umum: ambil shift pertama yang tersedia di sistem
        return Shift::first();
    }

    /**
     * Validasi radius kantor (Haversine) + akurasi GPS hanya untuk cabang yang diizinkan bagi karyawan tersebut.
     */
    private function ensureWithinRadius(ClockInRequest $request, int $employeeId): void
    {
        $employee = \App\Models\Employee::with(['assignments.branch'])->findOrFail($employeeId);

        if ($employee->can_clock_in_anywhere || $employee->attendance_location_type === 'anywhere') {
            return; // Bebas presensi di mana saja
        }

        $allowedBranches = $employee->getAllowedBranches();

        if ($allowedBranches->isEmpty()) {
            return; // Belum ada cabang berkoordinat yang disetel
        }

        $userLat = (float) $request->latitude;
        $userLng = (float) $request->longitude;

        $isInsideAllowedBranch = false;
        $minDistance = null;
        $nearestAllowedBranch = null;

        foreach ($allowedBranches as $branch) {
            if ($branch->latitude === null || $branch->longitude === null) continue;
            $radius = (int) ($branch->attendance_radius ?? 20);
            $dist = GeoLocation::haversineMeters(
                $userLat,
                $userLng,
                (float) $branch->latitude,
                (float) $branch->longitude
            );

            if ($minDistance === null || $dist < $minDistance) {
                $minDistance = $dist;
                $nearestAllowedBranch = $branch;
            }

            if ($dist <= $radius) {
                $isInsideAllowedBranch = true;
                break;
            }
        }

        if (!$isInsideAllowedBranch) {
            // Cek apakah user berada di cabang lain yang TIDAK berhak untuk karyawan ini
            $allOtherBranches = \App\Models\Branch::whereNotIn('id', $allowedBranches->pluck('id'))->get();
            $detectedOtherBranch = null;
            foreach ($allOtherBranches as $otherBranch) {
                if ($otherBranch->latitude === null || $otherBranch->longitude === null) continue;
                $otherRadius = (int) ($otherBranch->attendance_radius ?? 20);
                $otherDist = GeoLocation::haversineMeters(
                    $userLat,
                    $userLng,
                    (float) $otherBranch->latitude,
                    (float) $otherBranch->longitude
                );
                if ($otherDist <= $otherRadius) {
                    $detectedOtherBranch = $otherBranch;
                    break;
                }
            }

            $allowedNames = $allowedBranches->pluck('name')->join(', ');

            if ($detectedOtherBranch) {
                throw ValidationException::withMessages([
                    'latitude' => [
                        "Anda terdeteksi di area cabang '{$detectedOtherBranch->name}', namun Anda tidak berhak melakukan absensi di cabang tersebut. Anda hanya berhak absen di: {$allowedNames}."
                    ],
                ]);
            }

            $targetRadius = $nearestAllowedBranch ? (int) ($nearestAllowedBranch->attendance_radius ?? 20) : 20;
            $nearestName = $nearestAllowedBranch ? $nearestAllowedBranch->name : $allowedNames;
            throw ValidationException::withMessages([
                'latitude' => [
                    "Di luar radius cabang yang diizinkan untuk Anda (jarak " . round($minDistance ?? 0) . " m dari {$nearestName}, maks radius {$targetRadius} m). Anda hanya berhak absen di: {$allowedNames}."
                ],
            ]);
        }

        // Akurasi GPS > 100m dianggap tidak valid
        if (!$request->boolean('is_canvassing') && $request->accuracy > 100) {
            throw ValidationException::withMessages([
                'accuracy' => ["Akurasi GPS terlalu rendah (" . round($request->accuracy) . " m)."],
            ]);
        }
    }

    /**
     * Verifikasi wajah opsional saat clock-in.
     */
    private function verifyOptionalFace(int $employeeId, ?string $faceToken): bool
    {
        if (!$faceToken) {
            return false;
        }

        $face = EmployeeFace::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (!$face) {
            return false;
        }

        // Dummy match string; integrasi ML Kit asli di client Android.
        return hash_equals((string) $face->face_template, (string) $faceToken);
    }

    // ===== CLOCK IN =====

    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $employee = $request->user();

        // ============================================================
        // VERIFIKASI WAJAH (Hanya pastikan foto dikirim)
        // ============================================================
        if (!$request->filled('face_photo')) {
            return response()->json([
                'success' => false,
                'message' => 'Foto wajah wajib disertakan untuk absensi.',
                'data'    => ['face_required' => true],
            ], 422);
        }

        if (!$request->boolean('is_canvassing')) {
            $this->ensureWithinRadius($request, $employee->id);
        }

        $employeeShift = $this->activeShift($employee->id);
        $shift = $employeeShift?->shift;
        $isOffDayWork = false;

        if (!$shift) {
            // Karyawan dijadwalkan Libur/Off atau belum memiliki shift aktif untuk hari ini,
            // namun nyatanya hadir / masuk kerja: cari fallback shift otomatis agar tetap bisa clock-in.
            $shift = $this->resolveFallbackShift($employee);

            if (!$shift) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada shift yang dapat ditentukan untuk hari ini. Silakan hubungi admin.',
                    'data' => null,
                ], 422);
            }

            $isOffDayWork = true;

            // Catat atau perbarui employee_shifts untuk hari ini agar kalender dan rekap sinkron
            EmployeeShift::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'effective_date' => now()->toDateString(),
                ],
                [
                    'shift_id' => $shift->id,
                    'end_date' => now()->toDateString(),
                    'is_active' => true,
                ]
            );
        }

        // Cek jika ada sesi absensi aktif sebelumnya yang belum selesai clock-out
        $today = now()->toDateString();
        $unclosedPrevious = Attendance::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('date', '<', $today)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->latest('clock_in_at')
            ->first();

        if ($unclosedPrevious) {
            $prevShift = $unclosedPrevious->shift;
            $clockInAt = Carbon::parse($unclosedPrevious->clock_in_at);
            $isOvernight = $prevShift ? ($prevShift->cross_midnight || ($prevShift->clock_in && $prevShift->clock_out && $prevShift->clock_in > $prevShift->clock_out)) : false;

            if ($isOvernight && $prevShift && $prevShift->clock_out) {
                $prevDateStr = Carbon::parse($unclosedPrevious->date)->toDateString();
                $expectedClockOut = Carbon::parse($prevDateStr . ' ' . $prevShift->clock_out)->addDay();
                $autoClockOutThreshold = $expectedClockOut->copy()->addHours(4);
            } else {
                $expectedClockOut = $clockInAt->copy()->addHours($prevShift?->working_hours ?? 8);
                $autoClockOutThreshold = $clockInAt->copy()->addHours(16);
            }

            // Jika saat ini masih dalam rentang waktu toleransi aktif (misal shift malam / lembur)
            if (now()->lessThan($autoClockOutThreshold)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda masih memiliki sesi presensi tanggal ' . Carbon::parse($unclosedPrevious->date)->translatedFormat('d F Y') . ' yang belum clock-out. Silakan lakukan clock-out terlebih dahulu.',
                    'data' => new AttendanceResource($unclosedPrevious->load(['logs', 'shift'])),
                ], 409);
            } else {
                // Jika sudah lewat batas toleransi (sesi tertinggal lama), auto-close agar tidak menghalangi clock in hari ini
                $unclosedPrevious->clock_out_at = $expectedClockOut;
                $unclosedPrevious->working_hours = $prevShift?->working_hours ?? 8;
                $unclosedPrevious->save();
            }
        }

        // Cegah double clock-in pada hari yang sama.
        $today = now()->toDateString();
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->whereNotNull('clock_in_at')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah clock-in hari ini',
                'data' => new AttendanceResource($existing->load(['logs', 'shift'])),
            ], 409);
        }

        $now = now();

        // Status late: jika karyawan masuk pada hari liburnya (Off-Day Work), status otomatis 'present' (Hadir)
        // tanpa dikenakan potongan/penalti keterlambatan.
        if ($isOffDayWork) {
            $isLate = false;
            $lateMinutes = 0;
            $status = 'present';
        } else {
            // Clock-in melebihi waktu shift mulai + late_tolerance (mulai menit berikutnya, cth: 08:31 baru telat).
            $shiftClockIn = Carbon::parse($shift->clock_in, $now->getTimezone());
            $lateTolerance = (int) $shift->late_tolerance_minutes;
            $grace = $shiftClockIn->copy()->addMinutes($lateTolerance)->endOfMinute();
            $isLate = $now->greaterThan($grace);
            $lateMinutes = $isLate ? max((int) $shiftClockIn->copy()->addMinutes($lateTolerance)->diffInMinutes($now, false), 0) : 0;
            $status = $isLate ? 'late' : 'present';
        }

        $isFaceVerified = $this->verifyOptionalFace($employee->id, $request->face_token);

        $photoPath = null;
        if ($request->filled('face_photo')) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $request->face_photo);
            $base64 = str_replace([' ', "\r", "\n"], ['+', '', ''], $base64);
            $decoded = base64_decode($base64);
            if ($decoded) {
                $filename = 'attendance/in_' . time() . '_' . uniqid() . '.jpg';
                Storage::disk('public')->put($filename, $decoded);
                $photoPath = $filename;
            }
        }

        $primaryAssignment = $employee->primaryAssignment ?? $employee->assignments()->where('is_primary', true)->first();

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'employee_assignment_id' => $primaryAssignment?->id,
            'shift_id' => $shift->id,
            'date' => $today,
            'clock_in_at' => $now,
            'clock_in_lat' => $request->latitude,
            'clock_in_lng' => $request->longitude,
            'clock_in_accuracy' => $request->accuracy,
            'clock_in_photo' => $photoPath,
            'break_minutes' => 0,
            'working_hours' => 0,
            'late_minutes' => $isLate ? max($lateMinutes, 0) : 0,
            'overtime_minutes' => 0,
            'status' => $status,
            'notes' => $isOffDayWork ? 'Masuk pada hari libur / jadwal off' : null,
            'is_face_verified' => $isFaceVerified,
        ]);

        AttendanceLog::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'action' => 'clock_in',
            'action_at' => $now,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'device_info' => $request->device_info,
            'is_face_verified' => $isFaceVerified,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clock-in berhasil',
            'data' => new AttendanceResource($attendance->load(['logs', 'shift'])),
        ], 201);
    }

    // ===== CLOCK OUT =====

    public function clockOut(ClockOutRequest $request): JsonResponse
    {
        $employee = $request->user();

        // ============================================================
        // VERIFIKASI WAJAH (Hanya pastikan foto dikirim) – Clock Out
        // ============================================================
        if (!$request->filled('face_photo')) {
            return response()->json([
                'success' => false,
                'message' => 'Foto wajah wajib disertakan untuk clock-out.',
                'data'    => ['face_required' => true],
            ], 422);
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->latest('clock_in_at')
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada absensi aktif untuk clock-out',
                'data' => null,
            ], 422);
        }

        $now = now();
        $clockIn = Carbon::parse($attendance->clock_in_at);

        // Working hours = total selisih clock_in → clock_out dikurangi break (menit).
        $totalMinutes = $clockIn->diffInMinutes($now);
        $effectiveMinutes = max($totalMinutes - (int) $attendance->break_minutes, 0);

        $photoPath = null;
        if ($request->filled('face_photo')) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $request->face_photo);
            $base64 = str_replace([' ', "\r", "\n"], ['+', '', ''], $base64);
            $decoded = base64_decode($base64);
            if ($decoded) {
                $filename = 'attendance/out_' . time() . '_' . uniqid() . '.jpg';
                Storage::disk('public')->put($filename, $decoded);
                $photoPath = $filename;
            }
        }

        $attendance->clock_out_at = $now;
        $attendance->working_hours = round($effectiveMinutes / 60, 2);
        $attendance->clock_out_lat = $request->latitude;
        $attendance->clock_out_lng = $request->longitude;
        if ($photoPath) {
            $attendance->clock_out_photo = $photoPath;
        }

        // Hitung overtime hanya jika ada pengajuan lembur yang sudah di-approve oleh semua pihak
        // dan waktu lembur dihitung per jam mulai dari jam 20:00 sampai selesai
        $dateStr = Carbon::parse($attendance->date)->toDateString();
        $otRequest = \App\Models\OvertimeRequest::where('employee_id', $attendance->employee_id)
            ->whereDate('date', $dateStr)
            ->where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere(function ($sub) {
                      $sub->whereHas('approval', function ($appr) {
                          $appr->where('status', 'approved');
                      });
                  });
            })
            ->first();

        if ($otRequest) {
            $otStart = $otRequest->requested_start_time ? Carbon::parse($otRequest->requested_start_time)->format('H:i:s') : '20:00:00';
            $otStartTime = Carbon::parse($dateStr . ' ' . $otStart, $now->getTimezone());
            if ($now->greaterThan($otStartTime)) {
                $attendance->overtime_minutes = max((int) $otStartTime->diffInMinutes($now), 0);
            } else {
                $attendance->overtime_minutes = 0;
            }
        } else {
            $attendance->overtime_minutes = 0;
        }

        $attendance->save();

        AttendanceLog::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'action' => 'clock_out',
            'action_at' => $now,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'device_info' => $request->device_info,
        ]);

        // Tutup sesi tracking lokasi aktif
        \App\Models\LocationTrackingSession::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'stopped_at' => $now,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Clock-out berhasil',
            'data' => new AttendanceResource($attendance->load(['logs', 'shift'])),
        ]);
    }

    // ===== BREAK / RETURN BREAK =====

    public function takeBreak(BreakRequest $request): JsonResponse
    {
        $employee = $request->user();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->latest('clock_in_at')
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada absensi aktif',
                'data' => null,
            ], 422);
        }

        if ($attendance->break_start_at && !$attendance->break_end_at) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sedang dalam sesi break',
                'data' => new AttendanceResource($attendance->load('logs')),
            ], 409);
        }

        $attendance->break_start_at = now();
        $attendance->save();

        AttendanceLog::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'action' => 'break',
            'action_at' => now(),
            'device_info' => $request->device_info,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Break dimulai',
            'data' => new AttendanceResource($attendance->load(['logs', 'shift'])),
        ]);
    }

    public function returnBreak(BreakRequest $request): JsonResponse
    {
        $employee = $request->user();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->latest('clock_in_at')
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada absensi aktif',
                'data' => null,
            ], 422);
        }

        if (!($attendance->break_start_at && !$attendance->break_end_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Break belum dimulai atau sudah berakhir',
                'data' => new AttendanceResource($attendance->load('logs')),
            ], 409);
        }

        $now = now();
        $breakMinutes = (int) $attendance->break_minutes
            + (int) Carbon::parse($attendance->break_start_at)->diffInMinutes($now);

        $attendance->break_end_at = $now;
        $attendance->break_minutes = $breakMinutes;
        $attendance->save();

        AttendanceLog::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'action' => 'return_break',
            'action_at' => $now,
            'device_info' => $request->device_info,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Break selesai',
            'data' => new AttendanceResource($attendance->load(['logs', 'shift'])),
        ]);
    }

    // ===== HISTORY =====

    public function history(HistoryRequest $request): JsonResponse
    {
        $employee = $request->user();

        $query = Attendance::with(['logs', 'shift'])
            ->where('employee_id', $employee->id);

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $history = $query->orderByDesc('date')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat absensi',
            'data' => AttendanceResource::collection($history)->response()->getData(true),
        ]);
    }

    // ===== LOCATION SYNC (CANVASSING) =====
    public function syncLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
        ]);

        $employee = $request->user();
        $today = now()->toDateString();

        // Cari atau buat session tracking untuk hari ini (berdasarkan is_active)
        $session = \App\Models\LocationTrackingSession::firstOrCreate(
            ['employee_id' => $employee->id, 'is_active' => true],
            ['started_at' => now(), 'mode' => 'working_hours']
        );

        // Simpan titik lokasi
        \App\Models\EmployeeLocation::create([
            'employee_id' => $employee->id,
            'mode' => $session->mode,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy ?? 0,
            'recorded_at' => now(),
        ]);

        // Update employee last location
        $employee->last_latitude = $request->latitude;
        $employee->last_longitude = $request->longitude;
        $employee->save();

        // Broadcast to WebSocket
        broadcast(new \App\Events\LiveLocationUpdated(
            $employee->id,
            $employee->name,
            $request->latitude,
            $request->longitude
        ))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Location synced'
        ]);
    }
}

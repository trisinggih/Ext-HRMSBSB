import re

with open('app/Http/Controllers/Api/v1/Mobile/MobileOvertimeController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# I will replace the requestOvertime and clockIn methods to strictly follow the rules
new_methods = """
    public function requestOvertime(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'date' => 'required|date',
            'reason' => 'required|string',
            'requested_end_time' => 'required|date_format:H:i',
        ]);

        $employeeId = $request->user()->employee->id;
        $shift = \\\\App\Models\Shift::find($request->shift_id);

        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'Shift tidak ditemukan'], 404);
        }

        // Rule: Lembur harus setelah shift selesai.
        // Kita asumsikan pengajuan dilakukan di hari yang sama dengan shift
        $now = now();
        $shiftClockOut = \\\\Carbon\\\\Carbon::parse($request->date . ' ' . $shift->clock_out);
        
        // Bisa jadi user belum melewati jam shift.
        if ($now->lessThan($shiftClockOut)) {
            return response()->json([
                'success' => false,
                'message' => 'Lembur hanya bisa diajukan setelah jam shift reguler selesai (' . $shift->clock_out . ')'
            ], 400);
        }

        // Rule: Lembur dimulai jam seperti di form shift bagian "Maks. Pengajuan Lembur"
        // Jika kosong, gunakan jam clock_out shift.
        $requestedStartTime = $shift->max_overtime_request_time ?: $shift->clock_out;

        $existing = OvertimeRequest::where('employee_id', $employeeId)
            ->where('date', $request->date)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengajukan lembur untuk tanggal ini',
            ], 400);
        }

        $overtimeRequest = OvertimeRequest::create([
            'employee_id' => $employeeId,
            'shift_id' => $request->shift_id,
            'date' => $request->date,
            'reason' => $request->reason,
            'requested_start_time' => $requestedStartTime,
            'requested_end_time' => $request->requested_end_time,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dibuat',
            'data' => $overtimeRequest,
        ], 201);
    }

    public function clockIn(Request $request): JsonResponse
    {
        $employeeId = $request->user()->employee->id;
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

        if ($overtimeRequest->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Pengajuan lembur Anda belum disetujui (Status: ' . $overtimeRequest->status . ')'], 400);
        }

        if ($attendance->overtime_clock_in_at) {
            return response()->json(['success' => false, 'message' => 'Anda sudah melakukan clock in lembur'], 400);
        }

        $attendance->update([
            'overtime_clock_in_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil clock in lembur',
            'data' => $attendance,
        ]);
    }
"""

start_idx = content.find("public function requestOvertime"); end_idx = content.find("public function clockOut"); content = content[:start_idx] + new_methods + "\n    public function clockOut" + content[end_idx + len("public function clockOut"):]

with open('app/Http/Controllers/Api/v1/Mobile/MobileOvertimeController.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("MobileOvertimeController updated.")

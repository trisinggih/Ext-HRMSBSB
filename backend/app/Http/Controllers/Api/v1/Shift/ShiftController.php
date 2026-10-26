<?php

namespace App\Http\Controllers\Api\v1\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreEmployeeShiftRequest;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateEmployeeShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Http\Resources\Shift\EmployeeShiftResource;
use App\Http\Resources\Shift\ShiftResource;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    // ===== CRUD SHIFT =====

    public function index(Request $request): JsonResponse
    {
        $shifts = Shift::query()
            ->with('company:id,name')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar shift',
            'data' => ShiftResource::collection($shifts)->response()->getData(true),
        ]);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $data = $request->validated();

        $shift = Shift::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil dibuat',
            'data' => new ShiftResource($shift->load('company')),
        ], 201);
    }

    public function show(Shift $shift): JsonResponse
    {
        $shift->load(['company', 'employeeShifts']);
        $data = new ShiftResource($shift);
        $data->additional([
            'employee_shifts' => EmployeeShiftResource::collection($shift->employeeShifts),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail shift',
            'data' => $data,
        ]);
    }

    public function update(UpdateShiftRequest $request, Shift $shift): JsonResponse
    {
        $shift->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil diperbarui',
            'data' => new ShiftResource($shift->load('company')),
        ]);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $shift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===== CRUD EMPLOYEE SHIFT (schedule per employee) =====

    public function employeeShifts(Request $request): JsonResponse
    {
        $query = EmployeeShift::query()->with(['employee', 'shift.company']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }
        if ($request->filled('month')) {
            if (strlen($request->month) === 7 && str_contains($request->month, '-')) {
                [$year, $month] = explode('-', $request->month);
                $query->whereYear('effective_date', $year)->whereMonth('effective_date', $month);
            } else {
                $query->whereMonth('effective_date', $request->month);
                if ($request->filled('year')) {
                    $query->whereYear('effective_date', $request->year);
                }
            }
        } elseif ($request->filled('year')) {
            $query->whereYear('effective_date', $request->year);
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('effective_date', 'like', "%{$search}%")
                  ->orWhereHas('shift', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('sort_by')) {
            $query->orderBy($request->sort_by, $request->get('sort_dir', 'asc'));
        } else {
            $query->orderBy('effective_date', 'asc')->orderBy('id', 'asc');
        }

        $list = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar jadwal shift karyawan',
            'data' => EmployeeShiftResource::collection($list)->response()->getData(true),
        ]);
    }

    public function storeEmployeeShift(StoreEmployeeShiftRequest $request): JsonResponse
    {
        $payload = $request->validated();
        if (!isset($payload['effective_date'])) {
            $payload['effective_date'] = now()->toDateString();
        }

        $employeeShift = EmployeeShift::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal shift karyawan berhasil dibuat',
            'data' => new EmployeeShiftResource(
                $employeeShift->load(['employee', 'shift.company'])
            ),
        ], 201);
    }

    public function showEmployeeShift(EmployeeShift $employeeShift): JsonResponse
    {
        $employeeShift->load(['employee', 'shift.company']);

        return response()->json([
            'success' => true,
            'message' => 'Detail jadwal shift karyawan',
            'data' => new EmployeeShiftResource($employeeShift),
        ]);
    }

    public function updateEmployeeShift(UpdateEmployeeShiftRequest $request, EmployeeShift $employeeShift): JsonResponse
    {
        $employeeShift->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Jadwal shift karyawan berhasil diperbarui',
            'data' => new EmployeeShiftResource($employeeShift->load(['employee', 'shift.company'])),
        ]);
    }

    public function destroyEmployeeShift(EmployeeShift $employeeShift): JsonResponse
    {
        $employeeShift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal shift karyawan berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===== CRUD EMPLOYEE SHIFT PATTERN =====
    public function patterns(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
        ]);

        $patterns = \App\Models\EmployeeShiftPattern::with('shift')
            ->where('employee_id', $request->employee_id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $patterns
        ]);
    }

    public function upsertPattern(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'patterns' => 'required|array',
            'patterns.*.day_of_week' => 'required|integer|min:0|max:6',
            'patterns.*.shift_id' => 'nullable|integer|exists:shifts,id',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            foreach ($request->patterns as $pattern) {
                \App\Models\EmployeeShiftPattern::updateOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'day_of_week' => $pattern['day_of_week']
                    ],
                    [
                        'shift_id' => $pattern['shift_id'] ?? null
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pola shift berhasil disimpan'
        ]);
    }

    public function generateShifts(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'year' => 'required|integer|min:2020',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $employeeId = $request->employee_id;
        $patterns = \App\Models\EmployeeShiftPattern::where('employee_id', $employeeId)
            ->get()->keyBy('day_of_week');

        if ($patterns->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Pola shift belum diatur untuk karyawan ini.'
            ], 400);
        }

        $startOfMonth = \Carbon\Carbon::create($request->year, $request->month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        
        $inserts = [];
        $now = now();
        
        $generatedCount = 0;
        
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek; // 0 (Minggu) - 6 (Sabtu)
            $pattern = $patterns->get($dayOfWeek);

            \App\Models\EmployeeShift::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'effective_date' => $date->toDateString(),
                ],
                [
                    'shift_id' => $pattern ? $pattern->shift_id : null,
                    'end_date' => $date->toDateString(),
                    'is_active' => true,
                    'updated_at' => $now,
                ]
            );
            $generatedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => $generatedCount . ' shift berhasil digenerate untuk bulan ' . $startOfMonth->translatedFormat('F Y')
        ]);
    }

    public function calendar(Request $request): JsonResponse
    {
        if (!$request->filled('employee_id') && $request->user()) {
            $request->merge(['employee_id' => $request->user()->id]);
        }

        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'year' => 'required|integer|min:2020',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $employeeId = $request->employee_id;
        
        if ($request->user()->tokenCan('employee') && !$request->user()->tokenCan('admin')) {
            if ($request->user()->id != $employeeId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $startOfMonth = \Carbon\Carbon::create($request->year, $request->month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Cek jika ada absensi yang belum clock-out dari sebelum awal bulan ini (misal shift malam tanggal 31 ke tanggal 1)
        $prevUnclosed = \App\Models\Attendance::with('shift')
            ->where('employee_id', $employeeId)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->whereDate('date', '<', $startOfMonth->toDateString())
            ->latest('date')
            ->first();

        $queryStartDate = $prevUnclosed ? \Carbon\Carbon::parse($prevUnclosed->date)->startOfDay() : $startOfMonth;

        // Ambil data shift dalam rentang tersebut
        $shifts = \App\Models\EmployeeShift::with('shift')
            ->where('employee_id', $employeeId)
            ->whereBetween('effective_date', [$queryStartDate->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn ($item) => \Carbon\Carbon::parse($item->effective_date)->toDateString());

        $attendances = \App\Models\Attendance::with('shift')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$queryStartDate->toDateString(), $endOfMonth->toDateString()])
            ->get();

        // Auto-close absensi tertinggal di masa lalu yang belum clock-out dan sudah lewat batas toleransi
        $now = now();
        $today = $now->toDateString();
        foreach ($attendances as $att) {
            $attDate = \Carbon\Carbon::parse($att->date)->toDateString();
            if ($attDate < $today && !empty($att->clock_in_at) && empty($att->clock_out_at)) {
                $shift = $att->shift ?? ($shifts->get($attDate)?->shift);
                $clockInAt = \Carbon\Carbon::parse($att->clock_in_at);
                $isOvernight = $shift ? ($shift->cross_midnight || ($shift->clock_in && $shift->clock_out && $shift->clock_in > $shift->clock_out)) : false;

                if ($isOvernight && $shift && $shift->clock_out) {
                    $expectedClockOut = \Carbon\Carbon::parse($attDate . ' ' . $shift->clock_out)->addDay();
                    $threshold = $expectedClockOut->copy()->addHours(4);
                } else {
                    $expectedClockOut = $clockInAt->copy()->addHours($shift?->working_hours ?? 8);
                    $threshold = $clockInAt->copy()->addHours(16);
                }

                if ($now->greaterThanOrEqualTo($threshold)) {
                    $att->clock_out_at = $expectedClockOut;
                    $att->working_hours = $shift?->working_hours ?? 8;
                    $att->save();
                }
            }
        }
        $attendances = $attendances->keyBy(fn ($item) => \Carbon\Carbon::parse($item->date)->toDateString());

        // Ambil data lembur yang disetujui dalam bulan tersebut
        $overtimeRequests = \App\Models\OvertimeRequest::where('employee_id', $employeeId)
            ->whereBetween('date', [$queryStartDate->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('status', ['approved', 'in_progress', 'done'])
            ->get()
            ->keyBy(fn ($item) => \Carbon\Carbon::parse($item->date)->toDateString());

        // Ambil data laporan keterlambatan dalam bulan tersebut
        $lateRequests = \App\Models\LateRequest::where('employee_id', $employeeId)
            ->whereBetween('date', [$queryStartDate->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn ($item) => \Carbon\Carbon::parse($item->date)->toDateString());

        $calendar = [];
        for ($date = $queryStartDate->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dateString = $date->toDateString();
            $shiftRecord = $shifts->get($dateString);
            $attendance = $attendances->get($dateString);
            $lateReq = $lateRequests->get($dateString);
            $lateReason = $lateReq?->reason ?? $attendance?->notes;

            $calendar[] = [
                'date' => $dateString,
                'day_of_week' => $date->dayOfWeek,
                'is_holiday' => $shiftRecord ? is_null($shiftRecord->shift_id) : true,
                'has_approved_overtime' => $overtimeRequests->has($dateString),
                'late_reason' => $lateReason,
                'shift' => $shiftRecord && $shiftRecord->shift ? [
                    'id' => $shiftRecord->shift->id,
                    'name' => $shiftRecord->shift->name,
                    'clock_in' => $shiftRecord->shift->clock_in,
                    'clock_out' => $shiftRecord->shift->clock_out,
                    'cross_midnight' => (bool) ($shiftRecord->shift->cross_midnight || ($shiftRecord->shift->clock_in && $shiftRecord->shift->clock_out && $shiftRecord->shift->clock_in > $shiftRecord->shift->clock_out)),
                ] : null,
                'attendance' => $attendance ? [
                    'id' => $attendance->id,
                    'clock_in' => $attendance->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i:s') : null,
                    'clock_out' => $attendance->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i:s') : null,
                    'overtime_in' => $attendance->overtime_clock_in_at ? \Carbon\Carbon::parse($attendance->overtime_clock_in_at)->format('H:i:s') : null,
                    'overtime_out' => $attendance->overtime_clock_out_at ? \Carbon\Carbon::parse($attendance->overtime_clock_out_at)->format('H:i:s') : null,
                    'late_minutes' => $attendance->late_minutes,
                    'status' => $attendance->status,
                    'notes' => $attendance->notes,
                    'late_reason' => $lateReason,
                ] : ($lateReq ? [
                    'id' => null,
                    'clock_in' => null,
                    'clock_out' => null,
                    'overtime_in' => null,
                    'overtime_out' => null,
                    'late_minutes' => $lateReq->late_minutes,
                    'status' => 'late',
                    'notes' => $lateReq->reason,
                    'late_reason' => $lateReq->reason,
                ] : null),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Kalender karyawan',
            'data' => $calendar,
        ]);
    }

    public function generateAllShifts(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020',
            'month' => 'required|integer|min:1|max:12',
            'patterns' => 'required|array',
            'patterns.*.day_of_week' => 'required|integer|min:0|max:6',
            'patterns.*.shift_id' => 'nullable|integer|exists:shifts,id',
        ]);

        $employees = \App\Models\Employee::where('is_active', true)->pluck('id');
        if ($employees->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada karyawan aktif.'], 400);
        }

        $startOfMonth = \Carbon\Carbon::create($request->year, $request->month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $now = now();

        \DB::beginTransaction();
        try {
            $patternsData = [];
            foreach ($employees as $empId) {
                foreach ($request->patterns as $pattern) {
                    \App\Models\EmployeeShiftPattern::updateOrCreate(
                        [
                            'employee_id' => $empId,
                            'day_of_week' => $pattern['day_of_week'],
                        ],
                        [
                            'shift_id' => $pattern['shift_id'],
                            'updated_at' => $now,
                        ]
                    );
                }
            }

            $patternsCollection = collect($request->patterns)->keyBy('day_of_week');

            for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                $dayOfWeek = $date->dayOfWeek;
                $pattern = $patternsCollection->get($dayOfWeek);
                $shiftId = $pattern ? $pattern['shift_id'] : null;

                foreach ($employees as $empId) {
                    \App\Models\EmployeeShift::updateOrCreate(
                        [
                            'employee_id' => $empId,
                            'effective_date' => $date->toDateString(),
                        ],
                        [
                            'shift_id' => $shiftId,
                            'end_date' => $date->toDateString(),
                            'is_active' => true,
                            'updated_at' => $now,
                        ]
                    );
                }
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil generate shift untuk semua karyawan.'
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate shift: ' . $e->getMessage()
            ], 500);
        }
    }
}

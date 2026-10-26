<?php

namespace App\Http\Controllers\Api\v1\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Exports\GenericExport;
use App\Exports\AttendanceDailyExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    /**
     * Admin view of all attendance history
     */
    public function history(Request $request): JsonResponse
    {
        $query = Attendance::with(['logs', 'shift', 'employee']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('month')) {
            $year = substr($request->month, 0, 4);
            $month = substr($request->month, 5, 2);
            $query->whereYear('date', $year)->whereMonth('date', $month);
        }
        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($sq) use ($search) {
                $sq->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('name', 'like', "%{$search}%")
                       ->orWhere('employee_id', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $history = $query->orderByDesc('date')->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat absensi (Admin)',
            'data' => AttendanceResource::collection($history)->response()->getData(true),
        ]);
    }

    /**
     * Daily attendance report (Schedule + Clock In/Out) supports date range and export
     */
    public function daily(Request $request)
    {
        $startDate = $request->query('start_date', now()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        
        $query = Employee::query()
            ->when(!$request->boolean('include_executives') && !$request->boolean('for_payroll'), fn ($q) => $q->withoutExecutives())
            ->where(function ($q) use ($startDate, $endDate) {
                // 1. Punya data absensi nyata pada rentang tanggal ini (tetap ditampilkan jika ada absensinya)
                $q->whereHas('attendances', function ($aq) use ($startDate, $endDate) {
                    $aq->whereBetween('date', [$startDate, $endDate]);
                })
                // 2. Karyawan aktif dan belum melewati tanggal terminasi sebelum startDate
                ->orWhere(function ($activeQ) use ($startDate) {
                    $activeQ->where('is_active', true)
                        ->whereDoesntHave('terminations', function ($tq) use ($startDate) {
                            $tq->whereIn('status', ['approved', 'completed'])
                               ->where('termination_date', '<', $startDate);
                        });
                })
                // 3. Karyawan memiliki tanggal terminasi yang belum lewat sebelum startDate (termination_date >= startDate)
                ->orWhereHas('terminations', function ($tq) use ($startDate) {
                    $tq->whereIn('status', ['approved', 'completed'])
                       ->where('termination_date', '>=', $startDate);
                })
                // 4. Karyawan non-aktif tanpa terminasi resmi, dinonaktifkan pada atau setelah startDate
                ->orWhere(function ($iq) use ($startDate) {
                    $iq->where('is_active', false)
                       ->whereDoesntHave('terminations')
                       ->whereDate('updated_at', '>=', $startDate);
                });
            })
            ->distinct()->with([
            'terminations' => fn($tq) => $tq->whereIn('status', ['approved', 'completed'])->orderByDesc('termination_date'),
            'shifts' => function($q) use ($startDate, $endDate) {
                $q->where('effective_date', '<=', $endDate)
                  ->where(function($sq) use ($startDate) {
                      $sq->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                  })->where('is_active', true)
                  // Sort: specific date-bound shifts first (end_date NOT null), then by most recent effective
                  ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END ASC')
                  ->orderByDesc('effective_date')
                  ->with('shift');
            },
            'attendances' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])->with('shift');
            },
            'assignments.company:id,name',
            'assignments.branch:id,name',
            'assignments.department:id,name',
            'assignments.division:id,name',
            'assignments.position:id,name',
        ]);

        if ($request->filled('company_id')) {
            $query->whereHas('assignments', fn($q) => $q->where('company_id', $request->company_id));
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('assignments', fn($q) => $q->where('branch_id', $request->branch_id));
        }
        if ($request->filled('department_id')) {
            $query->whereHas('assignments', fn($q) => $q->where('department_id', $request->department_id));
        }
        if ($request->filled('division_id')) {
            $query->whereHas('assignments', fn($q) => $q->where('division_id', $request->division_id));
        }
        if ($request->filled('position_id')) {
            $query->whereHas('assignments', fn($q) => $q->where('position_id', $request->position_id));
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('assignments.position', fn($pq) => $pq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('assignments.department', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('assignments.branch', fn($bq) => $bq->where('name', 'like', "%{$search}%"));
            });
        }

        $isExport = $request->query('export') == '1';

        $employees = $isExport ? $query->orderBy('name')->get() : $query->orderBy('name')->paginate($request->integer('per_page', 50));

        $dataRows = collect();
        $employeeList = $isExport ? $employees : $employees->getCollection();
        $empIds = $employeeList->pluck('id');

        $sickRequests = \App\Models\SickLeaveRequest::whereIn('employee_id', $empIds)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        $permissionRequests = \App\Models\PermissionRequest::whereIn('employee_id', $empIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $leaveRequests = \App\Models\LeaveRequest::with('leaveType')
            ->whereIn('employee_id', $empIds)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        $lateRequests = \App\Models\LateRequest::whereIn('employee_id', $empIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        foreach ($employeeList as $emp) {
            $term = $emp->terminations->first();
            $termDate = $term ? ($term->termination_date instanceof \Carbon\Carbon ? $term->termination_date->toDateString() : substr((string)$term->termination_date, 0, 10)) : null;

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateStr = $d->toDateString();

                $att = $emp->attendances->first(function($a) use ($dateStr) {
                    return $a->date && $a->date->toDateString() === $dateStr;
                });

                // Cek status terminasi & keaktifan pada tanggal ini:
                // Acuan: tanggal termination untuk kehadiran.
                // 1. Jika di hari tersebut masih ada absensinya ($att), selalu tampilkan.
                // 2. Jika tidak ada absensi dan tanggalnya sudah melewati tanggal termination ($dateStr > $termDate), jangan tampilkan.
                // 3. Jika non-aktif tanpa record terminasi resmi dan sudah melewati tanggal non-aktifnya, jangan tampilkan.
                if (!$att) {
                    if ($termDate && $dateStr > $termDate) {
                        continue;
                    } elseif (!$emp->is_active && !$termDate) {
                        $inactiveDate = $emp->updated_at ? $emp->updated_at->timezone('Asia/Jakarta')->toDateString() : null;
                        if ($inactiveDate && $dateStr > $inactiveDate) {
                            continue;
                        } elseif (!$inactiveDate) {
                            continue;
                        }
                    }
                }
                
                // Find effective shift for this date
                // Priority: specific date-bound shift (end_date not null) over open-ended shift
                $matchingShifts = $emp->shifts->filter(function($s) use ($dateStr) {
                    $effDate = $s->effective_date instanceof \Carbon\Carbon
                        ? $s->effective_date->toDateString()
                        : (string) $s->effective_date;
                    $endDate2 = $s->end_date ? ($s->end_date instanceof \Carbon\Carbon
                        ? $s->end_date->toDateString()
                        : (string) $s->end_date) : null;
                    return $effDate <= $dateStr && (!$endDate2 || $endDate2 >= $dateStr);
                });
                // Prefer specific (has end_date) over permanent (no end_date)
                $empShift = $matchingShifts->first(fn($s) => !is_null($s->end_date))
                         ?? $matchingShifts->first();

                $shiftName = $empShift ? ($empShift->shift?->name ?? 'Off / Libur') : 'Belum Ada Jadwal / Libur';
                $shiftTime = ($empShift && $empShift->shift) ? ($empShift->shift->clock_in . ' - ' . $empShift->shift->clock_out) : '-';
                
                $status = $att ? $att->status : null;
                
                // Determine base status when no record exists
                if (!$status && (!$empShift || !$empShift->shift)) {
                    $status = 'off';
                } elseif (!$status) {
                    $status = '-';
                }

                // Recalculate late minutes purely from clock_in time vs shift start time + late tolerance
                $lateMinutes = 0;
                if ($att && $att->clock_in_at && $empShift && $empShift->shift && $empShift->shift->clock_in) {
                    // Use only the H:i portion to avoid any timezone comparison issues
                    $clockInTime  = (int) $att->clock_in_at->format('H') * 60 + (int) $att->clock_in_at->format('i');
                    $shiftInParts = explode(':', $empShift->shift->clock_in); // e.g. "08:30:00"
                    $shiftInTime  = (int) ($shiftInParts[0] ?? 0) * 60 + (int) ($shiftInParts[1] ?? 0);
                    $tolerance    = (int) ($empShift->shift->late_tolerance_minutes ?? 0);

                    if ($clockInTime > ($shiftInTime + $tolerance)) {
                        $lateMinutes = $clockInTime - ($shiftInTime + $tolerance);
                    }
                }

                // If lateMinutes is 0 and status was recorded as 'late', normalize status to 'present'
                if ($lateMinutes === 0 && $status === 'late') {
                    $status = 'present';
                }

                // Override status to 'late' if lateMinutes > 0
                if ($lateMinutes > 0 && !in_array($status, ['sick', 'leave', 'permit', 'off', '-'])) {
                    $status = 'late';
                }

                // Determine notes / keterangan:
                // 1. If an attendance record exists for this date, use its explicit notes (if any).
                // 2. Only fall back to request reasons (Sick/Permit/Leave/Late) if NO attendance record exists yet.
                $notes = null;
                if ($att) {
                    $notes = ($att->notes && $att->notes !== '-') ? $att->notes : null;
                } else {
                    $sick = $sickRequests->first(function($r) use ($emp, $dateStr) {
                        $sDate = $r->start_date ? ($r->start_date instanceof \Carbon\Carbon ? $r->start_date->toDateString() : (string)$r->start_date) : null;
                        $eDate = $r->end_date ? ($r->end_date instanceof \Carbon\Carbon ? $r->end_date->toDateString() : (string)$r->end_date) : null;
                        return $r->employee_id == $emp->id && $sDate <= $dateStr && $eDate >= $dateStr;
                    });
                    if ($sick) {
                        $notes = "Sakit: " . ($sick->reason ?: '-');
                        if (!$status || $status === '-') $status = 'sick';
                    }

                    if (!$notes) {
                        $perm = $permissionRequests->first(function($r) use ($emp, $dateStr) {
                            $pDate = $r->date ? ($r->date instanceof \Carbon\Carbon ? $r->date->toDateString() : (string)$r->date) : null;
                            $eDate = $r->end_date ? ($r->end_date instanceof \Carbon\Carbon ? $r->end_date->toDateString() : (string)$r->end_date) : $pDate;
                            return $r->employee_id == $emp->id && $pDate <= $dateStr && $eDate >= $dateStr;
                        });
                        if ($perm) {
                            $notes = "Izin (" . ($perm->type ?: 'Izin') . "): " . ($perm->reason ?: '-');
                            if (!$status || $status === '-') $status = 'permit';
                        }
                    }

                    if (!$notes) {
                        $leave = $leaveRequests->first(function($r) use ($emp, $dateStr) {
                            $sDate = $r->start_date ? ($r->start_date instanceof \Carbon\Carbon ? $r->start_date->toDateString() : (string)$r->start_date) : null;
                            $eDate = $r->end_date ? ($r->end_date instanceof \Carbon\Carbon ? $r->end_date->toDateString() : (string)$r->end_date) : null;
                            return $r->employee_id == $emp->id && $sDate <= $dateStr && $eDate >= $dateStr;
                        });
                        if ($leave) {
                            $leaveTypeName = $leave->leaveType?->name ?? 'Cuti';
                            $notes = "Cuti (" . $leaveTypeName . "): " . ($leave->reason ?: '-');
                            if (!$status || $status === '-') $status = 'leave';
                        }
                    }

                    if (!$notes) {
                        $late = $lateRequests->first(function($r) use ($emp, $dateStr) {
                            $lDate = $r->date ? ($r->date instanceof \Carbon\Carbon ? $r->date->toDateString() : (string)$r->date) : null;
                            return $r->employee_id == $emp->id && $lDate === $dateStr;
                        });
                        if ($late) {
                            $notes = "Terlambat: " . ($late->reason ?: '-');
                        }
                    }
                }

                $clockInPhotoUrl = ($att && $att->clock_in_photo) ? asset('storage/' . $att->clock_in_photo) : null;
                $clockOutPhotoUrl = ($att && $att->clock_out_photo) ? asset('storage/' . $att->clock_out_photo) : null;
                $employeePhotoUrl = $emp->photo ? asset('storage/' . $emp->photo) : ($emp->face_photo ? asset('storage/' . $emp->face_photo) : null);

                // Resolve attachment URL for sick / permission / leave / manual attendance
                $attachmentPath = $att?->attachment;
                if (!$attachmentPath) {
                    $matchedSick = $sickRequests->first(function($r) use ($emp, $dateStr) {
                        $sDate = $r->start_date ? ($r->start_date instanceof \Carbon\Carbon ? $r->start_date->toDateString() : (string)$r->start_date) : null;
                        $eDate = $r->end_date ? ($r->end_date instanceof \Carbon\Carbon ? $r->end_date->toDateString() : (string)$r->end_date) : null;
                        return $r->employee_id == $emp->id && $sDate <= $dateStr && $eDate >= $dateStr && !empty($r->medical_certificate);
                    });
                    if ($matchedSick) {
                        $attachmentPath = $matchedSick->medical_certificate;
                    }
                }
                if (!$attachmentPath) {
                    $matchedPerm = $permissionRequests->first(function($r) use ($emp, $dateStr) {
                        $pDate = $r->date ? ($r->date instanceof \Carbon\Carbon ? $r->date->toDateString() : (string)$r->date) : null;
                        $eDate = $r->end_date ? ($r->end_date instanceof \Carbon\Carbon ? $r->end_date->toDateString() : (string)$r->end_date) : $pDate;
                        return $r->employee_id == $emp->id && $pDate <= $dateStr && $eDate >= $dateStr && !empty($r->attachment);
                    });
                    if ($matchedPerm) {
                        $attachmentPath = $matchedPerm->attachment;
                    }
                }
                if (!$attachmentPath) {
                    $matchedLeave = $leaveRequests->first(function($r) use ($emp, $dateStr) {
                        $sDate = $r->start_date ? ($r->start_date instanceof \Carbon\Carbon ? $r->start_date->toDateString() : (string)$r->start_date) : null;
                        $eDate = $r->end_date ? ($r->end_date instanceof \Carbon\Carbon ? $r->end_date->toDateString() : (string)$r->end_date) : null;
                        return $r->employee_id == $emp->id && $sDate <= $dateStr && $eDate >= $dateStr && !empty($r->attachment);
                    });
                    if ($matchedLeave) {
                        $attachmentPath = $matchedLeave->attachment;
                    }
                }
                $attachmentUrl = $attachmentPath ? asset('storage/' . $attachmentPath) : null;

                $workingHours = $att ? (float) $att->working_hours : 0;
                if ($workingHours <= 0 && $att && $att->clock_in_at && $att->clock_out_at) {
                    $diffMinutes = $att->clock_in_at->diffInMinutes($att->clock_out_at);
                    $breakMins = (int) ($att->break_minutes ?? 0);
                    if ($breakMins <= 0 && $att->break_start_at && $att->break_end_at) {
                        $breakMins = $att->break_start_at->diffInMinutes($att->break_end_at);
                    }
                    $workingHours = round(max($diffMinutes - $breakMins, 0) / 60, 2);
                }

                $dataRows->push([
                    'id' => $emp->id,
                    'date' => $dateStr,
                    'employee_id' => $emp->employee_id ?: ($emp->employee_code ?: '-'),
                    'name' => $emp->name,
                    'employee_code' => $emp->employee_code,
                    'position' => $emp->assignments->pluck('position.name')->filter()->join(', '),
                    'department' => $emp->assignments->pluck('department.name')->filter()->join(', '),
                    'branch' => $emp->assignments->pluck('branch.name')->filter()->join(', '),
                    'shift_name' => $shiftName,
                    'shift_time' => $shiftTime,
                    'shift_id' => $empShift ? $empShift->shift?->id : null,
                    'attendance_id' => $att ? $att->id : null,
                    'clock_in_at' => ($att && $att->clock_in_at) ? $att->clock_in_at->format('H:i') : null,
                    'clock_out_at' => ($att && $att->clock_out_at) ? $att->clock_out_at->format('H:i') : null,
                    'clock_in_photo' => $clockInPhotoUrl,
                    'clock_out_photo' => $clockOutPhotoUrl,
                    'employee_photo' => $employeePhotoUrl,
                    'break_start_at' => ($att && $att->break_start_at) ? $att->break_start_at->format('H:i') : null,
                    'break_end_at' => ($att && $att->break_end_at) ? $att->break_end_at->format('H:i') : null,
                    'status' => $status,
                    'working_hours' => $workingHours,
                    'late_minutes' => $lateMinutes,
                    'notes' => $notes ?: '-',
                    'attachment_url' => $attachmentUrl,
                ]);
            }
        }

        if ($request->filled('status')) {
            $targetStatus = $request->status;
            $dataRows = $dataRows->filter(function($row) use ($targetStatus) {
                if ($targetStatus === 'late') {
                    return $row['status'] === 'late' || ($row['status'] === 'present' && ($row['late_minutes'] ?? 0) > 0);
                }
                return $row['status'] === $targetStatus;
            })->values();
        }

        if ($request->filled('search')) {
            $s = strtolower(trim($request->search));
            $dataRows = $dataRows->filter(function($row) use ($s) {
                return str_contains(strtolower($row['name'] ?? ''), $s)
                    || str_contains(strtolower($row['employee_id'] ?? ''), $s)
                    || str_contains(strtolower($row['employee_code'] ?? ''), $s)
                    || str_contains(strtolower($row['position'] ?? ''), $s)
                    || str_contains(strtolower($row['department'] ?? ''), $s)
                    || str_contains(strtolower($row['branch'] ?? ''), $s)
                    || str_contains(strtolower($row['notes'] ?? ''), $s)
                    || str_contains(strtolower($row['shift_name'] ?? ''), $s)
                    || str_contains(strtolower($row['status'] ?? ''), $s);
            })->values();
        }

        if ($isExport) {
            $cols = [
                'date'          => 'Tanggal',
                'employee_id'   => 'NIP',
                'name'          => 'Karyawan',
                'employee_code' => 'Kode Karyawan',
                'branch'        => 'Cabang',
                'department'    => 'Departemen',
                'shift_name'    => 'Shift',
                'shift_time'    => 'Jam Kerja',
                'clock_in_at'   => 'Clock In',
                'clock_out_at'  => 'Clock Out',
                'late_minutes'  => 'Telat (Mnt)',
                'working_hours' => 'Jam Kerja (Jam)',
                'status'        => 'Status',
                'notes'         => 'Keterangan',
            ];

            $headers = array_values($cols);
            $rows = $dataRows->map(function($row) use ($cols) {
                $r = [];
                foreach ($cols as $key => $label) {
                    $val = $row[$key] ?? '-';
                    if ($key === 'status') {
                        $map = [
                            'present' => 'HADIR',
                            'late'    => 'TERLAMBAT',
                            'absent'  => 'MANGKIR',
                            'leave'   => 'CUTI',
                            'permit'  => 'IZIN',
                            'sick'    => 'SAKIT',
                            'holiday' => 'LIBUR',
                            'off'     => 'LIBUR',
                            '-'       => 'BELUM ABSEN',
                        ];
                        $val = $map[$val] ?? strtoupper($val);
                    }
                    $r[] = $val;
                }
                return $r;
            })->toArray();

            $fileName  = "Laporan_Kehadiran_{$startDate}_sd_{$endDate}.xlsx";
            $companyId = $request->filled('company_id') ? (int) $request->company_id : null;
            return Excel::download(new AttendanceDailyExport($rows, $headers, $startDate, $endDate, $companyId), $fileName);
        }

        $dayCount = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        return response()->json([
            'success' => true,
            'message' => 'Laporan Kehadiran Harian',
            'data' => [
                'data' => $dataRows->values()->all(),
                'meta' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'per_page' => $employees->perPage() * $dayCount,
                    'total' => $employees->total() * $dayCount,
                ]
            ],
        ]);
    }

    /**
     * Store manual attendance (Admin)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,permit,permission,sick,leave,holiday,off',
            'clock_in_at' => 'nullable|date_format:H:i',
            'clock_out_at' => 'nullable|date_format:H:i',
            'break_start_at' => 'nullable|date_format:H:i',
            'break_end_at' => 'nullable|date_format:H:i',
            'late_minutes' => 'nullable|integer',
            'working_hours' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:10240',
        ]);

        $date = $request->date;
        $in = $request->clock_in_at ? $date . ' ' . $request->clock_in_at . ':00' : null;
        $out = $request->clock_out_at ? $date . ' ' . $request->clock_out_at . ':00' : null;
        $bStart = $request->break_start_at ? $date . ' ' . $request->break_start_at . ':00' : null;
        $bEnd = $request->break_end_at ? $date . ' ' . $request->break_end_at . ':00' : null;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $folder = ($request->status === 'sick') ? 'attachments/sick' : 'attachments/permission';
            $attachmentPath = $request->file('attachment')->store($folder, 'public');
        }

        $emp = \App\Models\Employee::with(['assignments', 'shifts' => function($q) use ($date) {
            $q->where('effective_date', '<=', $date)
              ->where(function($sq) use ($date) {
                  $sq->whereNull('end_date')->orWhere('end_date', '>=', $date);
              })->where('is_active', true)
              ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END ASC')
              ->orderByDesc('effective_date')
              ->with('shift');
        }])->find($request->employee_id);

        $matchedAssignId = null;
        $matchedShiftId = null;
        if ($emp) {
            $matchedAssign = $emp->assignments->first(function($a) use ($date) {
                if ($a->join_date && \Carbon\Carbon::parse($a->join_date)->toDateString() > $date) return false;
                if ($a->end_date && \Carbon\Carbon::parse($a->end_date)->toDateString() < $date) return false;
                return true;
            }) ?: ($emp->primaryAssignment ?? $emp->assignments->where('is_primary', true)->first());
            $matchedAssignId = $matchedAssign?->id;

            $empShift = $emp->shifts->first(fn($s) => !is_null($s->end_date)) ?? $emp->shifts->first();
            $matchedShiftId = $empShift?->shift_id;
        }

        $breakMinutes = 0;
        if ($bStart && $bEnd) {
            $breakMinutes = max(\Carbon\Carbon::parse($bStart)->diffInMinutes(\Carbon\Carbon::parse($bEnd)), 0);
        }

        $workingHours = $request->filled('working_hours') ? (float) $request->working_hours : null;
        if ($workingHours === null) {
            if ($in && $out) {
                $totalMinutes = \Carbon\Carbon::parse($in)->diffInMinutes(\Carbon\Carbon::parse($out));
                $effectiveMinutes = max($totalMinutes - $breakMinutes, 0);
                $workingHours = round($effectiveMinutes / 60, 2);
            } else {
                $workingHours = 0;
            }
        }

        $lateMinutes = $request->filled('late_minutes') ? (int) $request->late_minutes : null;
        if ($lateMinutes === null && $in && isset($empShift) && $empShift?->shift?->clock_in) {
            $clockInTime  = (int) \Carbon\Carbon::parse($in)->format('H') * 60 + (int) \Carbon\Carbon::parse($in)->format('i');
            $shiftInParts = explode(':', $empShift->shift->clock_in);
            $shiftInTime  = (int) ($shiftInParts[0] ?? 0) * 60 + (int) ($shiftInParts[1] ?? 0);
            $tolerance    = (int) ($empShift->shift->late_tolerance_minutes ?? 0);

            if ($clockInTime > ($shiftInTime + $tolerance)) {
                $lateMinutes = $clockInTime - ($shiftInTime + $tolerance);
            } else {
                $lateMinutes = 0;
            }
        }

        $payload = [
            'employee_assignment_id' => $matchedAssignId,
            'status' => $request->status,
            'clock_in_at' => $in,
            'clock_out_at' => $out,
            'break_start_at' => $bStart,
            'break_end_at' => $bEnd,
            'break_minutes' => $breakMinutes,
            'late_minutes' => $lateMinutes ?? ($request->late_minutes ?? 0),
            'working_hours' => $workingHours,
            'notes' => $request->notes,
        ];
        if ($matchedShiftId) {
            $payload['shift_id'] = $matchedShiftId;
        }
        if ($attachmentPath) {
            $payload['attachment'] = $attachmentPath;
        }

        $att = Attendance::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'date' => $date,
            ],
            $payload
        );

        // Sync with SickLeaveRequest / PermissionRequest
        if ($request->status === 'sick') {
            $sickData = [
                'total_days' => 1,
                'reason' => $request->notes ?: 'Sakit (Input Manual Admin)',
                'status' => 'approved',
            ];
            if ($attachmentPath) {
                $sickData['medical_certificate'] = $attachmentPath;
            }
            \App\Models\SickLeaveRequest::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'start_date' => $date,
                    'end_date' => $date,
                ],
                $sickData
            );
        } elseif (in_array($request->status, ['permission', 'permit'])) {
            $permData = [
                'type' => $request->status === 'permit' ? 'permit' : 'permission',
                'end_date' => $date,
                'reason' => $request->notes ?: 'Izin (Input Manual Admin)',
                'status' => 'approved',
            ];
            if ($attachmentPath) {
                $permData['attachment'] = $attachmentPath;
            }
            \App\Models\PermissionRequest::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'date' => $date,
                ],
                $permData
            );
        }

        if ($attachmentPath) {
            if ($request->status === 'sick') {
                \App\Models\SickLeaveRequest::where('employee_id', $request->employee_id)
                    ->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date)
                    ->update(['medical_certificate' => $attachmentPath]);
            } elseif (in_array($request->status, ['permission', 'permit'])) {
                \App\Models\PermissionRequest::where('employee_id', $request->employee_id)
                    ->where('date', '<=', $date)
                    ->whereRaw('COALESCE(end_date, date) >= ?', [$date])
                    ->update(['attachment' => $attachmentPath]);
            }
        }

        if ($request->filled('notes') && trim($request->notes) !== '') {
            $notesVal = trim($request->notes);
            \App\Models\PermissionRequest::where('employee_id', $request->employee_id)
                ->where('date', '<=', $date)
                ->whereRaw('COALESCE(end_date, date) >= ?', [$date])
                ->update(['reason' => $notesVal]);

            \App\Models\LateRequest::where('employee_id', $request->employee_id)
                ->where('date', $date)
                ->update(['reason' => $notesVal]);

            \App\Models\LeaveRequest::where('employee_id', $request->employee_id)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->update(['reason' => $notesVal]);

            \App\Models\SickLeaveRequest::where('employee_id', $request->employee_id)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->update(['reason' => $notesVal]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran berhasil disimpan',
            'data' => $att
        ]);
    }

}

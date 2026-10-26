<?php

namespace App\Http\Controllers\Api\v1\Phase6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warning\StoreWarningRequest;
use App\Http\Requests\Warning\UpdateWarningRequest;
use App\Http\Resources\Warning\WarningResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeWarning;
use App\Models\KpiAssessment;
use App\Models\ProbationEvaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WarningController extends Controller
{
    /**
     * GET /admin/warnings/flagged
     * Mendapatkan daftar karyawan yang ter-flagging perlu dievaluasi untuk Surat Peringatan (SP),
     * berdasarkan KPI rendah, absensi mangkir/terlambat berat, probation, atau eskalasi SP aktif.
     */
    public function flaggedCandidates(Request $request): JsonResponse
    {
        $kpiThreshold = (float)$request->input('kpi_threshold', 70.0);
        $days = (int)$request->input('days', 30);
        $search = $request->input('search');

        $flagged = [];

        // 1. KPI Rendah: final_score < threshold
        $lowKpiAssessments = KpiAssessment::with([
                'employee.assignments.department',
                'employee.assignments.position',
                'period',
            ])
            ->whereNotNull('final_score')
            ->where('final_score', '<', $kpiThreshold)
            ->orderByDesc('id')
            ->get();

        foreach ($lowKpiAssessments as $assessment) {
            $employee = $assessment->employee;
            if (!$employee || !$employee->is_active) {
                continue;
            }

            $empId = $employee->id;
            if (!isset($flagged[$empId])) {
                $deptName = $employee->department?->name ?? ($employee->primaryAssignment?->department?->name ?? '-');
                $posName = $employee->position?->name ?? ($employee->primaryAssignment?->position?->name ?? '-');

                $flagged[$empId] = [
                    'employee_id' => $empId,
                    'employee_code' => $employee->employee_id ?? '-',
                    'name' => $employee->name,
                    'department' => $deptName,
                    'position' => $posName,
                    'avatar' => $employee->avatar ?? null,
                    'flags' => [],
                    'active_warning' => null,
                    'history_warnings_count' => 0,
                    'suggested_type' => 'sp1',
                    'suggested_reason' => '',
                ];
            }

            $score = (float)$assessment->final_score;
            $severity = $score < 60 ? 'danger' : 'warning';
            $flagged[$empId]['flags'][] = [
                'category' => 'kpi',
                'badge' => $score < 60 ? 'KPI Sangat Kurang' : 'KPI Kurang',
                'severity' => $severity,
                'detail' => "Skor KPI " . number_format($score, 1) . " (Di bawah batas {$kpiThreshold}) pada periode " . ($assessment->period?->name ?? 'Terbaru'),
                'score' => $score,
            ];
        }

        // 2. Absensi: Mangkir (absent/alpha) atau Terlambat Berat dalam $days hari terakhir
        $cutoffDate = now()->subDays($days)->toDateString();
        $attendanceStats = Attendance::query()
            ->where('date', '>=', $cutoffDate)
            ->where(function ($q) {
                $q->whereIn('status', ['absent', 'alpha'])
                  ->orWhere('late_minutes', '>=', 60);
            })
            ->selectRaw('employee_id,
                SUM(CASE WHEN status IN ("absent", "alpha") THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN late_minutes >= 60 THEN 1 ELSE 0 END) as severe_late_days,
                SUM(late_minutes) as total_late_minutes')
            ->groupBy('employee_id')
            ->havingRaw('absent_days >= 1 OR severe_late_days >= 3')
            ->get();

        foreach ($attendanceStats as $stat) {
            $empId = $stat->employee_id;
            $employee = Employee::with(['assignments.department', 'assignments.position'])->find($empId);
            if (!$employee || !$employee->is_active) {
                continue;
            }

            if (!isset($flagged[$empId])) {
                $deptName = $employee->department?->name ?? ($employee->primaryAssignment?->department?->name ?? '-');
                $posName = $employee->position?->name ?? ($employee->primaryAssignment?->position?->name ?? '-');

                $flagged[$empId] = [
                    'employee_id' => $empId,
                    'employee_code' => $employee->employee_id ?? '-',
                    'name' => $employee->name,
                    'department' => $deptName,
                    'position' => $posName,
                    'avatar' => $employee->avatar ?? null,
                    'flags' => [],
                    'active_warning' => null,
                    'history_warnings_count' => 0,
                    'suggested_type' => 'sp1',
                    'suggested_reason' => '',
                ];
            }

            if ($stat->absent_days >= 1) {
                $flagged[$empId]['flags'][] = [
                    'category' => 'attendance',
                    'badge' => "Mangkir {$stat->absent_days} Hari",
                    'severity' => $stat->absent_days >= 3 ? 'danger' : 'warning',
                    'detail' => "Ketidakhadiran tanpa keterangan sebanyak {$stat->absent_days} hari dalam {$days} hari terakhir.",
                ];
            }

            if ($stat->severe_late_days >= 3) {
                $flagged[$empId]['flags'][] = [
                    'category' => 'tardiness',
                    'badge' => "Terlambat Berat ({$stat->severe_late_days}x)",
                    'severity' => 'warning',
                    'detail' => "Terlambat >60 menit sebanyak {$stat->severe_late_days} kali (Total {$stat->total_late_minutes} menit) dalam {$days} hari terakhir.",
                ];
            }
        }

        // 3. Evaluasi Masa Percobaan (Probation Evaluation) yang ada catatan / tidak lulus
        $probationEvaluations = ProbationEvaluation::with(['employee.assignments.department', 'employee.assignments.position'])
            ->where(function ($q) {
                $q->where('final_decision', 'terminated')
                  ->orWhere('has_oral_warning', true)
                  ->orWhere('has_sp1', true)
                  ->orWhere('final_score_hundred', '<', 60);
            })
            ->orderByDesc('id')
            ->get();

        foreach ($probationEvaluations as $pe) {
            $employee = $pe->employee;
            if (!$employee || !$employee->is_active) {
                continue;
            }
            $empId = $employee->id;

            if (!isset($flagged[$empId])) {
                $deptName = $employee->department?->name ?? ($employee->primaryAssignment?->department?->name ?? '-');
                $posName = $employee->position?->name ?? ($employee->primaryAssignment?->position?->name ?? '-');

                $flagged[$empId] = [
                    'employee_id' => $empId,
                    'employee_code' => $employee->employee_id ?? '-',
                    'name' => $employee->name,
                    'department' => $deptName,
                    'position' => $posName,
                    'avatar' => $employee->avatar ?? null,
                    'flags' => [],
                    'active_warning' => null,
                    'history_warnings_count' => 0,
                    'suggested_type' => 'sp1',
                    'suggested_reason' => '',
                ];
            }

            $flagged[$empId]['flags'][] = [
                'category' => 'probation',
                'badge' => 'Catatan Disiplin Probasi',
                'severity' => 'warning',
                'detail' => 'Terdapat catatan teguran / ketidaksesuaian evaluasi masa percobaan (Probation).',
            ];
        }

        // 4. Periksa Active Warnings & Riwayat SP untuk Penentuan Rekomendasi Eskalasi
        $result = [];
        foreach ($flagged as $empId => $data) {
            // Filter search jika ada
            if ($search) {
                $term = strtolower($search);
                if (
                    !str_contains(strtolower($data['name']), $term) &&
                    !str_contains(strtolower($data['employee_code']), $term) &&
                    !str_contains(strtolower($data['department']), $term)
                ) {
                    continue;
                }
            }

            $warnings = EmployeeWarning::where('employee_id', $empId)->orderByDesc('id')->get();
            $data['history_warnings_count'] = $warnings->count();

            $activeWarning = $warnings->first(function ($w) {
                return $w->status === 'active' && (!$w->expired_date || $w->expired_date->isFuture() || $w->expired_date->isToday());
            });

            if ($activeWarning) {
                $data['active_warning'] = [
                    'id' => $activeWarning->id,
                    'warning_type' => $activeWarning->warning_type,
                    'type_label' => strtoupper($activeWarning->warning_type),
                    'issue_date' => $activeWarning->issue_date?->format('Y-m-d'),
                    'expired_date' => $activeWarning->expired_date?->format('Y-m-d'),
                    'reason' => $activeWarning->reason,
                ];

                // Rekomendasi eskalasi
                if ($activeWarning->warning_type === 'warning') {
                    $data['suggested_type'] = 'sp1';
                } elseif ($activeWarning->warning_type === 'sp1') {
                    $data['suggested_type'] = 'sp2';
                } elseif ($activeWarning->warning_type === 'sp2') {
                    $data['suggested_type'] = 'sp3';
                } else {
                    $data['suggested_type'] = 'sp3';
                }
            } else {
                $data['suggested_type'] = 'sp1';
            }

            // Buat draft alasan rekomendasi
            $flagDetails = array_map(fn($f) => $f['detail'], $data['flags']);
            $reasonSummary = "Indikasi evaluasi disiplin & kinerja karyawan:\n- " . implode("\n- ", $flagDetails);
            if ($activeWarning) {
                $reasonSummary .= "\nCatatan: Karyawan saat ini memiliki " . strtoupper($activeWarning->warning_type) . " aktif. Disarankan eskalasi ke " . strtoupper($data['suggested_type']) . " bila terbukti terjadi pelanggaran berulang.";
            } else {
                $reasonSummary .= "\nDisarankan pembinaan formal atau penerbitan " . strtoupper($data['suggested_type']) . " sesuai Peraturan Perusahaan.";
            }

            $data['suggested_reason'] = $reasonSummary;
            $result[] = $data;
        }

        // Urutkan: yang memiliki flag severity 'danger' atau aktif warning di posisi atas
        usort($result, function ($a, $b) {
            $aDanger = collect($a['flags'])->contains('severity', 'danger') ? 1 : 0;
            $bDanger = collect($b['flags'])->contains('severity', 'danger') ? 1 : 0;
            if ($aDanger !== $bDanger) return $bDanger <=> $aDanger;
            return count($b['flags']) <=> count($a['flags']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar karyawan ter-flagging untuk review SP',
            'data' => $result,
        ]);
    }

    /** GET /admin /warnings?employee_id= */
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeWarning::query()->with('employee:id,employee_id,name');

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar warning/SP',
            'data' => [
                'items' => WarningResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /** POST /admin /warnings */
    public function store(StoreWarningRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('evidence')) {
            $data['evidence'] = $request->file('evidence')->store('warnings', 'public');
        } else {
            unset($data['evidence']);
        }

        $warning = EmployeeWarning::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Warning/SP berhasil dibuat',
            'data' => new WarningResource($warning),
        ], 201);
    }

    /** GET /admin /warnings/{warning} */
    public function show(EmployeeWarning $warning): JsonResponse
    {
        $warning->load('employee:id,employee_id,name');

        return response()->json([
            'success' => true,
            'message' => 'Detail warning/SP',
            'data' => new WarningResource($warning),
        ]);
    }

    /** PUT /admin /warnings/{warning} */
    public function update(UpdateWarningRequest $request, EmployeeWarning $warning): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('evidence')) {
            $new = $request->file('evidence')->store('warnings', 'public');
            if ($warning->evidence) {
                Storage::disk('public')->delete($warning->evidence);
            }
            $data['evidence'] = $new;
        } else {
            unset($data['evidence']);
        }

        $warning->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Warning/SP berhasil diperbarui',
            'data' => new WarningResource($warning->fresh()),
        ]);
    }

    /** DELETE /admin /warnings/{warning} */
    public function destroy(EmployeeWarning $warning): JsonResponse
    {
        if ($warning->evidence) {
            Storage::disk('public')->delete($warning->evidence);
        }
        $warning->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warning/SP berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===================== EMPLOYEE (data sendiri) =====================

    /** GET /employee /warnings */
    public function myIndex(Request $request): JsonResponse
    {
        $emp = $request->user();
        $items = EmployeeWarning::where('employee_id', $emp->id)->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Warning/SP saya',
            'data' => WarningResource::collection($items)->response()->getData(true),
        ]);
    }

    /** GET /admin/warnings/{warning}/pdf */
    public function downloadPdf(EmployeeWarning $warning)
    {
        $warning->load(['employee.latestSignature', 'branch']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.warning', ['warning' => $warning]);
        
        $filename = 'Surat_Peringatan_' . ($warning->employee->name ?? 'Karyawan') . '_' . $warning->issue_date . '.pdf';
        
        return $pdf->download($filename);
    }
}

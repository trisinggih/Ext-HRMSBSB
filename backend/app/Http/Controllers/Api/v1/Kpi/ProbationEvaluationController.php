<?php

namespace App\Http\Controllers\Api\v1\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ProbationEvaluation;
use App\Models\SalaryIncreaseProposal;
use App\Models\Attendance;
use App\Models\EmployeeWarning;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ProbationEvaluationController extends Controller
{
    /**
     * Default 10 Aspects for Probation Evaluation according to Form PEBYMOTOR
     */
    public static function getDefaultAspects(): array
    {
        return [
            // A. Kinerja (40%)
            [
                'category' => 'A. Kinerja (40%)',
                'key' => 'kinerja_1',
                'name' => 'Pemahaman terhadap pekerjaan',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            [
                'category' => 'A. Kinerja (40%)',
                'key' => 'kinerja_2',
                'name' => 'Kualitas hasil kerja',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            [
                'category' => 'A. Kinerja (40%)',
                'key' => 'kinerja_3',
                'name' => 'Ketelitian dan akurasi kerja',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            [
                'category' => 'A. Kinerja (40%)',
                'key' => 'kinerja_4',
                'name' => 'Produktivitas dan penyelesaian tugas',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            // B. Sikap Kerja (30%)
            [
                'category' => 'B. Sikap Kerja (30%)',
                'key' => 'sikap_1',
                'name' => 'Disiplin dan kepatuhan terhadap aturan',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            [
                'category' => 'B. Sikap Kerja (30%)',
                'key' => 'sikap_2',
                'name' => 'Kehadiran dan ketepatan waktu',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            [
                'category' => 'B. Sikap Kerja (30%)',
                'key' => 'sikap_3',
                'name' => 'Tanggung jawab terhadap pekerjaan',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            // C. Kerja Sama & Komunikasi (20%)
            [
                'category' => 'C. Kerja Sama & Komunikasi (20%)',
                'key' => 'kerjasama_1',
                'name' => 'Kerja sama dalam tim',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            [
                'category' => 'C. Kerja Sama & Komunikasi (20%)',
                'key' => 'kerjasama_2',
                'name' => 'Komunikasi dengan rekan kerja dan atasan',
                'weight' => 10,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            // D. Adaptasi & Pengembangan Diri (10%)
            [
                'category' => 'D. Adaptasi & Pengembangan Diri (10%)',
                'key' => 'adaptasi_1',
                'name' => 'Kemampuan beradaptasi dengan lingkungan kerja',
                'weight' => 5,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
            [
                'category' => 'D. Adaptasi & Pengembangan Diri (10%)',
                'key' => 'adaptasi_2',
                'name' => 'Inisiatif dan kemauan belajar',
                'weight' => 5,
                'rating' => 0,
                'score' => 0,
                'notes' => ''
            ],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $evaluations = ProbationEvaluation::query()
            ->with([
                'employee.primaryAssignment.position',
                'employee.primaryAssignment.department',
                'employee.primaryAssignment.division',
                'evaluator',
                'approvedByDeptManager',
                'approvedByHrdManager',
                'salaryProposal'
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$request->search}%")
                    ->orWhere('nip', 'like', "%{$request->search}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('final_decision'), fn ($q) => $q->where('final_decision', $request->final_decision))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $evaluations]);
    }

    public function show(ProbationEvaluation $probationEvaluation): JsonResponse
    {
        $probationEvaluation->load([
            'employee.primaryAssignment.position',
            'employee.primaryAssignment.department',
            'employee.primaryAssignment.division',
            'employee.salaryComponents',
            'evaluator',
            'approvedByDeptManager',
            'approvedByHrdManager',
            'salaryProposal.proposedBy',
            'salaryProposal.areaManager',
            'salaryProposal.accountingManager',
            'salaryProposal.hrdManager',
            'salaryProposal.director'
        ]);

        return response()->json(['success' => true, 'data' => $probationEvaluation]);
    }

    /**
     * Helper to auto-summarize employee attendance & warnings for probation evaluation form.
     * Automatically filters attendance and warnings from join date up to evaluation date.
     */
    public function employeeSummary(Employee $employee, Request $request): JsonResponse
    {
        $employee->load([
            'primaryAssignment.position',
            'primaryAssignment.department',
            'primaryAssignment.division',
            'primaryAssignment.company.group',
            'salaryComponents'
        ]);

        $assignment = $employee->primaryAssignment ?? $employee->assignments()->first();

        // 1. Tentukan tanggal awal join & tanggal penilaian
        $joinDate = $assignment?->join_date 
            ? \Carbon\Carbon::parse($assignment->join_date)->toDateString()
            : ($employee->created_at ? $employee->created_at->toDateString() : now()->subMonths(3)->toDateString());

        $startDate = $request->get('start_date') 
            ?? $request->get('probation_start_date') 
            ?? $joinDate;

        $evaluationDate = $request->get('evaluation_date') 
            ?? $request->get('end_date') 
            ?? now()->toDateString();

        // 2. Filter absensi dari tanggal join hingga tanggal penilaian
        $attendanceQuery = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $evaluationDate);

        $absentDays = (clone $attendanceQuery)->where('status', 'absent')->count();
        $permitDays = (clone $attendanceQuery)->whereIn('status', ['permit', 'permission', 'leave'])->count();
        $sickDays   = (clone $attendanceQuery)->where('status', 'sick')->count();
        $lateDays   = (clone $attendanceQuery)->where(function ($q) {
            $q->where('late_minutes', '>', 0)->orWhere('status', 'late');
        })->count();
        $halfDays   = (clone $attendanceQuery)->where(function ($q) {
            $q->where('notes', 'like', '%setengah%')
              ->orWhere('notes', 'like', '%1/2%')
              ->orWhere('notes', 'like', '%half%');
        })->count();

        // 3. Catatan Pelanggaran (SP) dari tanggal join hingga tanggal penilaian
        $warnings = EmployeeWarning::where('employee_id', $employee->id)
            ->where(function ($q) use ($startDate, $evaluationDate) {
                $q->where(function ($sub) use ($startDate, $evaluationDate) {
                    $sub->whereNotNull('issue_date')
                        ->whereDate('issue_date', '>=', $startDate)
                        ->whereDate('issue_date', '<=', $evaluationDate);
                })->orWhere(function ($sub) use ($startDate, $evaluationDate) {
                    $sub->whereNull('issue_date')
                        ->whereDate('created_at', '>=', $startDate)
                        ->whereDate('created_at', '<=', $evaluationDate);
                });
            })
            ->get();

        $hasOralWarning = $warnings->contains(function ($w) {
            $t = strtolower($w->warning_type ?? $w->warning_level ?? '');
            return str_contains($t, 'lisan') || str_contains($t, 'oral');
        });
        $hasSp1 = $warnings->contains(function ($w) {
            $t = strtolower($w->warning_type ?? $w->warning_level ?? '');
            return str_contains($t, 'sp-1') || str_contains($t, 'sp1') || str_contains($t, 'pertama');
        });
        $hasSp2 = $warnings->contains(function ($w) {
            $t = strtolower($w->warning_type ?? $w->warning_level ?? '');
            return str_contains($t, 'sp-2') || str_contains($t, 'sp2') || str_contains($t, 'kedua');
        });

        // Salary components mapping
        $salaryComponents = $employee->salaryComponents;
        $currentDaily = 0;
        $currentMonthly = 0;
        $currentAttendanceBonus = 0;
        $currentPositionAllowance = 0;
        $currentLeaderAllowance = 0;

        foreach ($salaryComponents as $sc) {
            $name = strtolower($sc->name ?? '');
            if (str_contains($name, 'harian')) {
                $currentDaily += $sc->amount;
            } elseif (str_contains($name, 'pokok') || str_contains($name, 'bulanan')) {
                $currentMonthly += $sc->amount;
            } elseif (str_contains($name, 'kerajinan') || str_contains($name, 'kehadiran')) {
                $currentAttendanceBonus += $sc->amount;
            } elseif (str_contains($name, 'tunjangan jabatan')) {
                $currentPositionAllowance += $sc->amount;
            } elseif (str_contains($name, 'leader') || str_contains($name, 'tunjangan leader')) {
                $currentLeaderAllowance += $sc->amount;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee,
                'assignment' => $assignment,
                'period' => [
                    'start_date' => $startDate,
                    'evaluation_date' => $evaluationDate,
                ],
                'attendance_summary' => [
                    'absent_days' => $absentDays,
                    'permit_days' => $permitDays,
                    'sick_days' => $sickDays,
                    'half_days' => $halfDays,
                    'late_days' => $lateDays,
                ],
                'disciplinary_summary' => [
                    'has_oral_warning' => $hasOralWarning,
                    'has_sp1' => $hasSp1,
                    'has_sp2' => $hasSp2,
                    'warnings_count' => $warnings->count(),
                ],
                'current_salary' => [
                    'daily' => $currentDaily,
                    'monthly' => $currentMonthly,
                    'attendance_bonus' => $currentAttendanceBonus,
                    'position_allowance' => $currentPositionAllowance,
                    'leader_allowance' => $currentLeaderAllowance,
                ],
                'default_aspects' => self::getDefaultAspects(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'evaluator_id' => 'nullable|exists:employees,id',
            'evaluation_date' => 'required|date',
            'probation_start_date' => 'nullable|date',
            'probation_end_date' => 'nullable|date',
            'probation_period_text' => 'nullable|string',
            'absent_days' => 'integer|min:0',
            'permit_days' => 'integer|min:0',
            'sick_days' => 'integer|min:0',
            'half_days' => 'integer|min:0',
            'late_days' => 'integer|min:0',
            'has_oral_warning' => 'boolean',
            'has_sp1' => 'boolean',
            'has_sp2' => 'boolean',
            'aspect_scores' => 'required|array',
            'strengths' => 'nullable|string',
            'improvements' => 'nullable|string',
            'final_decision' => 'required|in:passed_permanent,extend_probation,terminated',
            'decision_notes' => 'nullable|string',
            'status' => 'nullable|in:draft,submitted,approved,rejected',
        ]);

        $calculated = $this->calculateScores($validated['aspect_scores']);
        $validated['aspect_scores'] = $calculated['aspect_scores'];
        $validated['total_weighted_score'] = $calculated['total_weighted_score'];
        $validated['final_score_hundred'] = $calculated['final_score_hundred'];
        $validated['predicate'] = $calculated['predicate'];
        $validated['status'] = $validated['status'] ?? 'submitted';

        $evaluation = ProbationEvaluation::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Penilaian Probation berhasil dibuat',
            'data' => $evaluation->load(['employee', 'evaluator', 'salaryProposal'])
        ], 201);
    }

    public function update(Request $request, ProbationEvaluation $probationEvaluation): JsonResponse
    {
        $validated = $request->validate([
            'evaluator_id' => 'nullable|exists:employees,id',
            'evaluation_date' => 'sometimes|date',
            'probation_start_date' => 'nullable|date',
            'probation_end_date' => 'nullable|date',
            'probation_period_text' => 'nullable|string',
            'absent_days' => 'integer|min:0',
            'permit_days' => 'integer|min:0',
            'sick_days' => 'integer|min:0',
            'half_days' => 'integer|min:0',
            'late_days' => 'integer|min:0',
            'has_oral_warning' => 'boolean',
            'has_sp1' => 'boolean',
            'has_sp2' => 'boolean',
            'aspect_scores' => 'sometimes|array',
            'strengths' => 'nullable|string',
            'improvements' => 'nullable|string',
            'final_decision' => 'sometimes|in:passed_permanent,extend_probation,terminated',
            'decision_notes' => 'nullable|string',
            'status' => 'nullable|in:draft,submitted,approved,rejected',
        ]);

        if (isset($validated['aspect_scores'])) {
            $calculated = $this->calculateScores($validated['aspect_scores']);
            $validated['aspect_scores'] = $calculated['aspect_scores'];
            $validated['total_weighted_score'] = $calculated['total_weighted_score'];
            $validated['final_score_hundred'] = $calculated['final_score_hundred'];
            $validated['predicate'] = $calculated['predicate'];
        }

        $probationEvaluation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Penilaian Probation berhasil diperbarui',
            'data' => $probationEvaluation->fresh(['employee', 'evaluator', 'salaryProposal'])
        ]);
    }

    public function destroy(ProbationEvaluation $probationEvaluation): JsonResponse
    {
        $probationEvaluation->delete();
        return response()->json(['success' => true, 'message' => 'Data penilaian probation berhasil dihapus']);
    }

    public function saveSalaryProposal(Request $request, ProbationEvaluation $probationEvaluation): JsonResponse
    {
        $validated = $request->validate([
            'proposal_date' => 'required|date',
            'employment_duration_text' => 'nullable|string',
            'current_daily_salary' => 'numeric|min:0',
            'current_monthly_salary' => 'numeric|min:0',
            'current_attendance_bonus' => 'numeric|min:0',
            'current_position_allowance' => 'numeric|min:0',
            'current_leader_allowance' => 'numeric|min:0',
            'proposed_daily_salary_increase' => 'numeric|min:0',
            'proposed_monthly_salary_increase' => 'numeric|min:0',
            'proposed_attendance_bonus_increase' => 'numeric|min:0',
            'proposed_position_allowance_increase' => 'numeric|min:0',
            'proposed_leader_allowance_increase' => 'numeric|min:0',
            'reasons' => 'nullable|array',
            'reasons.*' => 'nullable|string',
            'proposed_by' => 'nullable|exists:employees,id',
            'area_manager_id' => 'nullable|exists:employees,id',
            'accounting_manager_id' => 'nullable|exists:employees,id',
            'hrd_manager_id' => 'nullable|exists:employees,id',
            'director_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|in:draft,pending_approval,approved,rejected',
        ]);

        $validated['employee_id'] = $probationEvaluation->employee_id;
        $validated['probation_evaluation_id'] = $probationEvaluation->id;

        // Auto calculate new salaries
        $validated['new_daily_salary'] = ($validated['current_daily_salary'] ?? 0) + ($validated['proposed_daily_salary_increase'] ?? 0);
        $validated['new_monthly_salary'] = ($validated['current_monthly_salary'] ?? 0) + ($validated['proposed_monthly_salary_increase'] ?? 0);
        $validated['new_attendance_bonus'] = ($validated['current_attendance_bonus'] ?? 0) + ($validated['proposed_attendance_bonus_increase'] ?? 0);
        $validated['new_position_allowance'] = ($validated['current_position_allowance'] ?? 0) + ($validated['proposed_position_allowance_increase'] ?? 0);
        $validated['new_leader_allowance'] = ($validated['current_leader_allowance'] ?? 0) + ($validated['proposed_leader_allowance_increase'] ?? 0);

        $proposal = SalaryIncreaseProposal::updateOrCreate(
            ['probation_evaluation_id' => $probationEvaluation->id],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Permohonan Kenaikan Gaji berhasil disimpan',
            'data' => $proposal->load(['employee', 'proposedBy', 'areaManager', 'accountingManager', 'hrdManager', 'director'])
        ]);
    }

    public function approve(Request $request, ProbationEvaluation $probationEvaluation): JsonResponse
    {
        $validated = $request->validate([
            'approved_by_dept_manager' => 'nullable|exists:employees,id',
            'approved_by_hrd_manager' => 'nullable|exists:employees,id',
            'status' => 'required|in:approved,rejected,submitted',
        ]);

        $probationEvaluation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status evaluasi probation berhasil diperbarui',
            'data' => $probationEvaluation
        ]);
    }

    public function exportEvaluationPdf(ProbationEvaluation $probationEvaluation)
    {
        $probationEvaluation->load([
            'employee.primaryAssignment.position',
            'employee.primaryAssignment.department',
            'employee.primaryAssignment.division',
            'employee.primaryAssignment.company.group',
            'employee.company.group',
            'evaluator',
            'approvedByDeptManager',
            'approvedByHrdManager'
        ]);

        $employee = $probationEvaluation->employee;
        $assignment = $employee?->primaryAssignment ?? $employee?->assignments()->first();
        $company = $assignment?->company ?? $employee?->company ?? Company::first();

        $branch = $assignment?->branch ?? $employee?->branch;

        // Resolusi logo base64 cabang / perusahaan untuk kop surat
        $logoBase64 = null;
        if ($branch && method_exists($branch, 'getEffectiveLogoBase64Attribute')) {
            $logoBase64 = $branch->effective_logo_base64;
        } elseif ($company && method_exists($company, 'getEffectiveLogoBase64Attribute')) {
            $logoBase64 = $company->effective_logo_base64;
        }
        if (!$logoBase64) {
            $group = \App\Models\CompanyGroup::first();
            if ($group && $group->logo && file_exists(storage_path('app/public/' . $group->logo))) {
                $p = storage_path('app/public/' . $group->logo);
                $mime = mime_content_type($p) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
            }
        }

        $pdf = Pdf::loadView('pdf.probation_evaluation_form', [
            'evaluation' => $probationEvaluation,
            'employee'   => $employee,
            'assignment' => $assignment,
            'company'    => $company,
            'logoBase64' => $logoBase64,
            'evaluator'  => $probationEvaluation->evaluator,
            'aspects'    => $probationEvaluation->aspect_scores ?? self::getDefaultAspects(),
        ])->setPaper('A4', 'portrait');

        $empName = str_replace(' ', '_', $employee->name ?? 'Employee');
        return $pdf->stream("Form_Penilaian_Karyawan_Probation_{$empName}.pdf");
    }

    public function exportSalaryPdf(ProbationEvaluation $probationEvaluation)
    {
        $probationEvaluation->load([
            'employee.primaryAssignment.position',
            'employee.primaryAssignment.department',
            'employee.primaryAssignment.division',
            'employee.primaryAssignment.company.group',
            'employee.company.group',
            'salaryProposal.proposedBy',
            'salaryProposal.areaManager',
            'salaryProposal.accountingManager',
            'salaryProposal.hrdManager',
            'salaryProposal.director'
        ]);

        $proposal = $probationEvaluation->salaryProposal;
        if (!$proposal) {
            return response()->json(['success' => false, 'message' => 'Form Permohonan Kenaikan Gaji belum dibuat untuk karyawan ini.'], 404);
        }

        $employee = $probationEvaluation->employee;
        $assignment = $employee?->primaryAssignment ?? $employee?->assignments()->first();
        $company = $assignment?->company ?? $employee?->company ?? Company::first();

        $branch = $assignment?->branch ?? $employee?->branch;

        // Resolusi logo base64 cabang / perusahaan untuk kop surat
        $logoBase64 = null;
        if ($branch && method_exists($branch, 'getEffectiveLogoBase64Attribute')) {
            $logoBase64 = $branch->effective_logo_base64;
        } elseif ($company && method_exists($company, 'getEffectiveLogoBase64Attribute')) {
            $logoBase64 = $company->effective_logo_base64;
        }
        if (!$logoBase64) {
            $group = \App\Models\CompanyGroup::first();
            if ($group && $group->logo && file_exists(storage_path('app/public/' . $group->logo))) {
                $p = storage_path('app/public/' . $group->logo);
                $mime = mime_content_type($p) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
            }
        }

        $pdf = Pdf::loadView('pdf.salary_increase_proposal_form', [
            'proposal'   => $proposal,
            'evaluation' => $probationEvaluation,
            'employee'   => $employee,
            'assignment' => $assignment,
            'company'    => $company,
            'logoBase64' => $logoBase64,
        ])->setPaper('A4', 'portrait');

        $empName = str_replace(' ', '_', $employee->name ?? 'Employee');
        return $pdf->stream("Form_Permohonan_Kenaikan_Gaji_{$empName}.pdf");
    }

    private function calculateScores(array $aspectScores): array
    {
        $totalWeightedScore = 0;

        foreach ($aspectScores as &$aspect) {
            $weight = (float)($aspect['weight'] ?? 10);
            $rating = (float)($aspect['rating'] ?? 0);
            $aspect['score'] = $weight * $rating;
            $totalWeightedScore += $aspect['score'];
        }

        $finalScoreHundred = $totalWeightedScore / 5.0;

        if ($finalScoreHundred >= 85) {
            $predicate = 'Sangat Baik';
        } elseif ($finalScoreHundred >= 70) {
            $predicate = 'Baik';
        } elseif ($finalScoreHundred >= 60) {
            $predicate = 'Cukup';
        } elseif ($finalScoreHundred >= 50) {
            $predicate = 'Kurang';
        } else {
            $predicate = 'Sangat Kurang';
        }

        return [
            'aspect_scores' => $aspectScores,
            'total_weighted_score' => $totalWeightedScore,
            'final_score_hundred' => $finalScoreHundred,
            'predicate' => $predicate,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\v1\Kpi;

use App\Http\Controllers\Controller;
use App\Models\KpiAssessment;
use App\Models\KpiAssessmentScore;
use App\Models\KpiTemplate;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiAssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isNonAdmin = ($user instanceof Employee && !$user->isSuperAdmin() && !$user->isStaffHr());

        if ($isNonAdmin && !$request->has('scope') && !$request->has('only_subordinates')) {
            $request->merge(['only_subordinates' => true]);
        }

        $query = KpiAssessment::query()
            ->with([
                'period',
                'employee.position',
                'employee.division',
                'employee.department',
                'employee.primaryAssignment.department',
                'employee.primaryAssignment.division',
                'employee.primaryAssignment.position',
                'template',
                'reviewer'
            ])
            ->when($request->filled('period_id'), fn ($q) => $q->where('period_id', $request->period_id))
            ->when($request->filled('period_type') && $request->period_type !== 'all', function ($q) use ($request) {
                $q->whereHas('period', fn ($pq) => $pq->where('type', $request->period_type));
            })
            ->when($request->boolean('only_subordinates') || ($isNonAdmin && $request->input('scope') === 'subordinates'), function ($q) use ($user) {
                if ($user instanceof Employee) {
                    $this->applySubordinatesFilter($q, $user);
                }
            })
            ->when(!$request->boolean('only_subordinates') && $request->filled('department_group') && $request->department_group !== 'all', function ($q) use ($request) {
                $this->applyDepartmentGroupFilter($q, $request->department_group);
            })
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('reviewer_id'), fn ($q) => $q->where('reviewer_id', $request->reviewer_id))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($sq) use ($request) {
                $sq->where('transaction_number', 'like', "%{$request->search}%")
                   ->orWhereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$request->search}%"));
            }))
            ->orderBy('created_at', 'desc');

        $items = (clone $query)->paginate($request->integer('per_page', 15));

        // Hitung count per kelompok departemen untuk tab UI
        $baseCountQuery = KpiAssessment::query()
            ->when($request->filled('period_id'), fn ($q) => $q->where('period_id', $request->period_id))
            ->when($request->filled('period_type') && $request->period_type !== 'all', fn ($q) => $q->whereHas('period', fn ($pq) => $pq->where('type', $request->period_type)));

        $fatCount = $this->applyDepartmentGroupFilter(clone $baseCountQuery, 'fat')->count();
        $hrgaCount = $this->applyDepartmentGroupFilter(clone $baseCountQuery, 'hrga')->count();
        $salesCount = $this->applyDepartmentGroupFilter(clone $baseCountQuery, 'sales')->count();
        $operasionalCount = $this->applyDepartmentGroupFilter(clone $baseCountQuery, 'operasional')->count();

        $counts = [
            'all' => $isNonAdmin ? $items->total() : (clone $baseCountQuery)->count(),
            'fat' => $fatCount,
            'hrga' => $hrgaCount,
            'sales' => $salesCount,
            'operasional' => $operasionalCount,
        ];

        return response()->json([
            'success'           => true,
            'data'              => $items,
            'department_counts' => $counts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:kpi_periods,id',
            'employee_id' => 'required|exists:employees,id',
            'template_id' => 'nullable|exists:kpi_templates,id',
            'reviewer_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|in:pending,in_review,approved',
            'reviewer_notes' => 'nullable|string',
        ], [
            'period_id.required' => 'Periode KPI wajib dipilih.',
            'period_id.exists' => 'Periode KPI tidak valid.',
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'employee_id.exists' => 'Karyawan tidak valid.',
        ]);

        $exists = KpiAssessment::where('period_id', $validated['period_id'])
            ->where('employee_id', $validated['employee_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Penilaian KPI untuk karyawan ini pada periode yang dipilih sudah ada.'
            ], 422);
        }

        $employee = Employee::with(['primaryAssignment', 'position', 'division'])->find($validated['employee_id']);

        // Auto-resolve template jika tidak dipilih
        if (empty($validated['template_id'])) {
            $assignment = $employee?->primaryAssignment ?? $employee?->assignments()->first();
            $positionId = $assignment?->position_id ?? $employee?->position_id;
            $divisionId = $assignment?->division_id ?? $employee?->division_id;

            $template = null;
            if ($positionId) {
                $template = KpiTemplate::where('is_active', true)
                    ->where('scope', 'position')
                    ->where('position_id', $positionId)
                    ->first();
            }
            if (!$template && $divisionId) {
                $template = KpiTemplate::where('is_active', true)
                    ->where('scope', 'division')
                    ->where('division_id', $divisionId)
                    ->first();
            }
            if (!$template) {
                $template = KpiTemplate::where('is_active', true)->first();
            }
            $validated['template_id'] = $template?->id;
        }

        // Auto-resolve reviewer (atasan) jika tidak dipilih
        if (empty($validated['reviewer_id'])) {
            $assignment = $employee?->primaryAssignment ?? $employee?->assignments()->first();
            $validated['reviewer_id'] = $assignment?->supervisor_id ?? $employee?->supervisor_id;
        }

        $validated['status'] = $validated['status'] ?? 'pending';

        $assessment = KpiAssessment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Penilaian KPI berhasil ditambahkan',
            'data' => $assessment->load(['period', 'employee', 'template', 'reviewer']),
        ], 201);
    }

    public function update(Request $request, KpiAssessment $kpiAssessment): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'sometimes|required|exists:kpi_periods,id',
            'employee_id' => 'sometimes|required|exists:employees,id',
            'template_id' => 'nullable|exists:kpi_templates,id',
            'reviewer_id' => 'nullable|exists:employees,id',
            'status' => 'nullable|in:pending,in_review,approved',
            'reviewer_notes' => 'nullable|string',
        ]);

        $periodId = $validated['period_id'] ?? $kpiAssessment->period_id;
        $employeeId = $validated['employee_id'] ?? $kpiAssessment->employee_id;
        $exists = KpiAssessment::where('period_id', $periodId)
            ->where('employee_id', $employeeId)
            ->where('id', '!=', $kpiAssessment->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Penilaian KPI untuk karyawan ini pada periode yang dipilih sudah ada.'
            ], 422);
        }

        $kpiAssessment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Penilaian KPI berhasil diperbarui',
            'data' => $kpiAssessment->fresh(['period', 'employee', 'template', 'reviewer']),
        ]);
    }

    public function destroy(KpiAssessment $kpiAssessment): JsonResponse
    {
        $kpiAssessment->scores()->delete();
        $kpiAssessment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Penilaian KPI berhasil dihapus',
        ]);
    }

    public function show(KpiAssessment $kpiAssessment): JsonResponse
    {
        $kpiAssessment->load([
            'period',
            'employee.position',
            'employee.division',
            'employee.department',
            'employee.primaryAssignment.position',
            'employee.primaryAssignment.division',
            'employee.primaryAssignment.department',
            'reviewer',
            'template.templateIndicators.indicator',
            'template.ratingScale.levels',
            'scores.templateIndicator.indicator',
            'scores.ratingLevel',
        ]);

        return response()->json(['success' => true, 'data' => $kpiAssessment]);
    }

    /**
     * Simpan/update skor per indikator dari form penilaian.
     */
    public function submitScore(Request $request, KpiAssessment $kpiAssessment): JsonResponse
    {
        $data = $request->validate([
            'scores' => 'required|array',
            'scores.*.template_indicator_id' => 'required|exists:kpi_template_indicators,id',
            'scores.*.rating_level_id' => 'nullable|exists:kpi_rating_levels,id',
            'scores.*.raw_score' => 'required|numeric|min:0',
            'scores.*.notes' => 'nullable|string',
            'reviewer_notes' => 'nullable|string',
        ]);

        $kpiAssessment->load('template.templateIndicators');
        $templateIndicatorsMap = $kpiAssessment->template?->templateIndicators->keyBy('id') ?? collect();
        $totalWeight = $kpiAssessment->template?->templateIndicators->sum('weight') ?: 100;
        
        // Hitung total bobot dari indikator yang dinilai pada transaksi ini
        $assessedWeightSum = collect($data['scores'])->sum(fn($s) => $templateIndicatorsMap->get($s['template_indicator_id'])?->weight ?? 0);
        $effectiveTotalWeight = $assessedWeightSum > 0 ? (float) $assessedWeightSum : (float) $totalWeight;

        // Simpan masing-masing skor
        foreach ($data['scores'] as $scoreData) {
            $ti = $templateIndicatorsMap->get($scoreData['template_indicator_id']);
            $weight = $ti ? (float) $ti->weight : 0;
            $weightedScore = $effectiveTotalWeight > 0 ? round(((float)$scoreData['raw_score'] * $weight) / $effectiveTotalWeight, 2) : 0;

            KpiAssessmentScore::updateOrCreate(
                [
                    'assessment_id' => $kpiAssessment->id,
                    'template_indicator_id' => $scoreData['template_indicator_id'],
                ],
                [
                    'rating_level_id' => $scoreData['rating_level_id'] ?? null,
                    'raw_score' => $scoreData['raw_score'],
                    'weighted_score' => $weightedScore,
                    'notes' => $scoreData['notes'] ?? null,
                ]
            );
        }

        // Hitung final score proporsional
        $kpiAssessment->load('scores.templateIndicator');
        $finalScore = $kpiAssessment->calculateFinalScore();

        // Update status + final score
        $kpiAssessment->update([
            'final_score' => $finalScore,
            'status' => 'in_review',
            'reviewer_notes' => $data['reviewer_notes'] ?? $kpiAssessment->reviewer_notes,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Penilaian berhasil disimpan',
            'data' => $kpiAssessment->fresh([
                'period',
                'employee.position',
                'employee.division',
                'employee.department',
                'employee.primaryAssignment.position',
                'employee.primaryAssignment.division',
                'employee.primaryAssignment.department',
                'reviewer',
                'template.templateIndicators.indicator',
                'template.ratingScale.levels',
                'scores.templateIndicator.indicator',
                'scores.ratingLevel',
            ]),
            'final_score' => $finalScore,
        ]);
    }

    /**
     * Approve penilaian (final)
     */
    public function approve(KpiAssessment $kpiAssessment): JsonResponse
    {
        $kpiAssessment->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Penilaian disetujui',
            'data' => $kpiAssessment->fresh([
                'period',
                'employee.position',
                'employee.division',
                'employee.department',
                'employee.primaryAssignment.position',
                'employee.primaryAssignment.division',
                'employee.primaryAssignment.department',
                'reviewer',
                'template.templateIndicators.indicator',
                'template.ratingScale.levels',
                'scores.templateIndicator.indicator',
                'scores.ratingLevel',
            ])
        ]);
    }

    /**
     * Reset penilaian ke pending
     */
    public function reset(KpiAssessment $kpiAssessment): JsonResponse
    {
        $kpiAssessment->scores()->delete();
        $kpiAssessment->update([
            'status' => 'pending',
            'final_score' => null,
            'reviewed_at' => null,
            'reviewer_notes' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Penilaian direset',
            'data' => $kpiAssessment->fresh([
                'period',
                'employee.position',
                'employee.division',
                'employee.department',
                'employee.primaryAssignment.position',
                'employee.primaryAssignment.division',
                'employee.primaryAssignment.department',
                'reviewer',
                'template.templateIndicators.indicator',
                'template.ratingScale.levels',
                'scores.templateIndicator.indicator',
                'scores.ratingLevel',
            ])
        ]);
    }

    /**
     * Laporan rekap KPI per periode
     */
    public function report(Request $request): JsonResponse
    {
        $user = $request->user();
        $isNonAdmin = ($user instanceof Employee && !$user->isSuperAdmin() && !$user->isStaffHr());

        $periodId = $request->period_id;
        $deptGroup = strtolower($request->input('department_group', ''));
        $periodType = strtolower($request->input('period_type', ''));

        $query = KpiAssessment::with([
            'employee.position',
            'employee.division',
            'employee.assignments.department',
            'employee.assignments.division',
            'employee.assignments.position',
            'template',
            'period'
        ])
            ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
            ->when($periodType, fn ($q) => $q->whereHas('period', fn ($pq) => $pq->where('type', $periodType)))
            ->when($request->boolean('only_subordinates') || ($isNonAdmin && $request->input('scope') === 'subordinates'), function ($q) use ($user) {
                if ($user instanceof Employee) {
                    $this->applySubordinatesFilter($q, $user);
                }
            })
            ->when(!$request->boolean('only_subordinates') && $request->input('scope') !== 'subordinates' && $deptGroup && $deptGroup !== 'all', function ($q) use ($deptGroup) {
                $this->applyDepartmentGroupFilter($q, $deptGroup);
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('final_score');

        $assessments = $query->get();

        $summary = [
            'total' => $assessments->count(),
            'completed' => $assessments->where('status', 'approved')->count(),
            'in_review' => $assessments->where('status', 'in_review')->count(),
            'pending' => $assessments->where('status', 'pending')->count(),
            'average_score' => round($assessments->whereNotNull('final_score')->avg('final_score'), 2),
            'highest_score' => $assessments->max('final_score'),
            'lowest_score' => $assessments->whereNotNull('final_score')->min('final_score'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'assessments' => $assessments,
            ],
        ]);
    }

    /**
     * Executive Dashboard Analytics KPI (Executive Dashboard, Database Karyawan, & Riwayat Siklus)
     */
    public function dashboardAnalytics(Request $request): JsonResponse
    {
        $periodId = $request->period_id;

        $query = KpiAssessment::with([
            'employee.primaryAssignment.department',
            'employee.primaryAssignment.position',
            'employee.primaryAssignment.division',
            'period'
        ])
        ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
        ->whereNotNull('final_score');

        $assessments = $query->get();

        // 1. Ringkasan Perusahaan
        $totalAssessed = $assessments->count();
        $avgScore = $totalAssessed > 0 ? round($assessments->avg('final_score'), 1) : 0;
        $maxScore = $totalAssessed > 0 ? round($assessments->max('final_score'), 1) : 0;
        $minScore = $totalAssessed > 0 ? round($assessments->min('final_score'), 1) : 0;

        // 2. Rating Counts
        $ratingA = $assessments->filter(fn($a) => (float)$a->final_score >= 100)->count();
        $ratingB = $assessments->filter(fn($a) => (float)$a->final_score >= 80 && (float)$a->final_score < 100)->count();
        $ratingC = $assessments->filter(fn($a) => (float)$a->final_score >= 70 && (float)$a->final_score < 80)->count();
        $ratingD = $assessments->filter(fn($a) => (float)$a->final_score < 70)->count();

        // 3. Rekap Per Departemen
        $departmentsMap = [];
        foreach ($assessments as $a) {
            $deptName = $a->employee?->primaryAssignment?->department?->name 
                     ?? $a->employee?->department?->name 
                     ?? 'Lainnya / General';
            if (!isset($departmentsMap[$deptName])) {
                $departmentsMap[$deptName] = [
                    'department' => $deptName,
                    'scores' => [],
                    'rating_a' => 0,
                    'rating_b' => 0,
                    'rating_cd' => 0,
                ];
            }
            $score = (float)$a->final_score;
            $departmentsMap[$deptName]['scores'][] = $score;
            if ($score >= 100) {
                $departmentsMap[$deptName]['rating_a']++;
            } elseif ($score >= 80) {
                $departmentsMap[$deptName]['rating_b']++;
            } else {
                $departmentsMap[$deptName]['rating_cd']++;
            }
        }

        $departmentRecap = [];
        foreach ($departmentsMap as $deptName => $data) {
            $count = count($data['scores']);
            $departmentRecap[] = [
                'department' => $deptName,
                'employee_count' => $count,
                'avg_score' => $count > 0 ? round(array_sum($data['scores']) / $count, 1) : 0,
                'max_score' => $count > 0 ? round(max($data['scores']), 1) : 0,
                'min_score' => $count > 0 ? round(min($data['scores']), 1) : 0,
                'rating_a' => $data['rating_a'],
                'rating_b' => $data['rating_b'],
                'rating_cd' => $data['rating_cd'],
            ];
        }

        usort($departmentRecap, fn($x, $y) => $y['avg_score'] <=> $x['avg_score']);

        // 4. Top 5 & Bottom 5 Karyawan
        $sortedAssessments = $assessments->sortByDesc('final_score')->values();

        $top5 = $sortedAssessments->take(5)->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->employee?->name ?? 'N/A',
            'department' => $a->employee?->primaryAssignment?->department?->name ?? $a->employee?->department?->name ?? '-',
            'score' => round((float)$a->final_score, 1),
        ]);

        $bottom5 = $sortedAssessments->reverse()->take(5)->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->employee?->name ?? 'N/A',
            'department' => $a->employee?->primaryAssignment?->department?->name ?? $a->employee?->department?->name ?? '-',
            'score' => round((float)$a->final_score, 1),
        ])->values();

        // 5. Top 10 Performers for Horizontal Chart
        $top10 = $sortedAssessments->take(10)->map(fn($a) => [
            'name' => $a->employee?->name ?? 'N/A',
            'score' => round((float)$a->final_score, 1),
        ]);

        // 6. Sebaran Skor Bins (<70, 70-79.9, 80-89.9, 90-99.9, 100+)
        $scoreDistribution = [
            '< 70' => $assessments->filter(fn($a) => (float)$a->final_score < 70)->count(),
            '70 - 79.9' => $assessments->filter(fn($a) => (float)$a->final_score >= 70 && (float)$a->final_score < 80)->count(),
            '80 - 89.9' => $assessments->filter(fn($a) => (float)$a->final_score >= 80 && (float)$a->final_score < 90)->count(),
            '90 - 99.9' => $assessments->filter(fn($a) => (float)$a->final_score >= 90 && (float)$a->final_score < 100)->count(),
            '100 ke atas' => $assessments->filter(fn($a) => (float)$a->final_score >= 100)->count(),
        ];

        // 7. Riwayat Skor Perusahaan Antar Siklus (Period History Trend)
        $allPeriods = \App\Models\KpiPeriod::orderBy('start_date', 'asc')->get();
        $periodHistory = [];

        foreach ($allPeriods as $p) {
            $pAssessments = KpiAssessment::where('period_id', $p->id)->whereNotNull('final_score')->get();
            $pCount = $pAssessments->count();
            if ($pCount > 0) {
                $periodHistory[] = [
                    'period_id' => $p->id,
                    'period_name' => $p->name,
                    'recorded_date' => $p->end_date ? \Carbon\Carbon::parse($p->end_date)->format('d/m/Y') : ($p->created_at ? $p->created_at->format('d/m/Y') : '-'),
                    'employee_count' => $pCount,
                    'avg_score' => round($pAssessments->avg('final_score'), 1),
                    'rating_a' => $pAssessments->filter(fn($a) => (float)$a->final_score >= 100)->count(),
                    'rating_b' => $pAssessments->filter(fn($a) => (float)$a->final_score >= 80 && (float)$a->final_score < 100)->count(),
                    'rating_cd' => $pAssessments->filter(fn($a) => (float)$a->final_score < 80)->count(),
                    'notes' => 'Siklus review periode ' . $p->name,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_assessed' => $totalAssessed,
                    'avg_score' => $avgScore,
                    'max_score' => $maxScore,
                    'min_score' => $minScore,
                ],
                'ratings' => [
                    'rating_a' => $ratingA,
                    'rating_b' => $ratingB,
                    'rating_c' => $ratingC,
                    'rating_d' => $ratingD,
                ],
                'department_recap' => $departmentRecap,
                'top_5' => $top5,
                'bottom_5' => $bottom5,
                'top_10' => $top10,
                'score_distribution' => $scoreDistribution,
                'period_history' => $periodHistory,
            ]
        ]);
    }

    /**
     * Export PDF Penilaian Per Orang (Single Assessment)
     */
    public function exportSinglePdf(KpiAssessment $kpiAssessment)
    {
        $kpiAssessment->load([
            'period',
            'employee.primaryAssignment.company',
            'employee.primaryAssignment.position',
            'employee.primaryAssignment.division',
            'reviewer',
            'template.templateIndicators.indicator',
            'template.ratingScale.levels',
            'scores.templateIndicator.indicator',
            'scores.ratingLevel',
        ]);

        $employee = $kpiAssessment->employee;
        $assignment = $employee?->primaryAssignment ?? $employee?->assignments()->first();
        $company = $assignment?->company ?? \App\Models\Company::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.kpi_assessment', [
            'assessment'         => $kpiAssessment,
            'employee'           => $employee,
            'assignment'         => $assignment,
            'company'            => $company,
            'reviewer'           => $kpiAssessment->reviewer,
            'templateIndicators' => $kpiAssessment->template?->templateIndicators ?? [],
            'ratingLevels'       => $kpiAssessment->template?->ratingScale?->levels ?? [],
            'scores'             => $kpiAssessment->scores ?? [],
        ])->setPaper('A4', 'portrait');

        $empName = str_replace(' ', '_', $employee->name ?? 'Employee');
        return $pdf->stream("KPI_Assessment_{$empName}.pdf");
    }

    /**
     * Export PDF Laporan Rekapitulasi KPI (All Reports)
     */
    public function exportReportPdf(Request $request)
    {
        $user = $request->user();
        $isNonAdmin = ($user instanceof Employee && !$user->isSuperAdmin() && !$user->isStaffHr());

        $periodId = $request->period_id;
        $deptGroup = strtolower($request->input('department_group', ''));
        $periodType = strtolower($request->input('period_type', ''));

        $query = KpiAssessment::with([
            'employee.primaryAssignment.position',
            'employee.primaryAssignment.division',
            'template',
            'period'
        ])
        ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
        ->when($periodType, fn ($q) => $q->whereHas('period', fn ($pq) => $pq->where('type', $periodType)))
        ->when($request->boolean('only_subordinates') || ($isNonAdmin && $request->input('scope') === 'subordinates'), function ($q) use ($user) {
            if ($user instanceof Employee) {
                $this->applySubordinatesFilter($q, $user);
            }
        })
        ->when(!$request->boolean('only_subordinates') && $request->input('scope') !== 'subordinates' && $deptGroup && $deptGroup !== 'all', function ($q) use ($deptGroup) {
            $this->applyDepartmentGroupFilter($q, $deptGroup);
        })
        ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
        ->orderByDesc('final_score');

        $assessments = $query->get();

        $summary = [
            'total'         => $assessments->count(),
            'completed'     => $assessments->where('status', 'approved')->count(),
            'in_review'     => $assessments->where('status', 'in_review')->count(),
            'pending'       => $assessments->where('status', 'pending')->count(),
            'average_score' => round($assessments->whereNotNull('final_score')->avg('final_score'), 2),
            'highest_score' => $assessments->max('final_score'),
            'lowest_score'  => $assessments->whereNotNull('final_score')->min('final_score'),
        ];

        $period  = $periodId ? \App\Models\KpiPeriod::find($periodId) : null;
        $company = \App\Models\Company::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.kpi_report', [
            'assessments' => $assessments,
            'summary'     => $summary,
            'period'      => $period,
            'company'     => $company,
        ])->setPaper('A4', 'landscape');

        $periodStr = $period ? str_replace(' ', '_', $period->name) : 'All';
        return $pdf->stream("Laporan_Rekapitulasi_KPI_{$periodStr}.pdf");
    }

    /**
     * Filter query khusus anak buah / bawahan langsung dari atasan (Head/SPV/Manager)
     */
    private function applySubordinatesFilter($query, Employee $user)
    {
        $managedDivs = is_array($user->managed_division_ids) ? $user->managed_division_ids : [];
        return $query->where(function ($q) use ($user, $managedDivs) {
            $q->where('reviewer_id', $user->id)
              ->orWhereHas('employee.primaryAssignment', fn ($aq) => $aq->where('supervisor_id', $user->id))
              ->orWhereHas('employee.assignments', fn ($aq) => $aq->where('supervisor_id', $user->id));

            if (!empty($managedDivs)) {
                $q->orWhereHas('employee.primaryAssignment', fn ($aq) => $aq->whereIn('division_id', $managedDivs))
                  ->orWhereHas('employee.assignments', fn ($aq) => $aq->whereIn('division_id', $managedDivs));
            }
        });
    }

    /**
     * Filter query berdasarkan kelompok departemen / divisi secara akurat
     */
    private function applyDepartmentGroupFilter($query, string $group)
    {
        $group = strtolower(trim($group));
        if ($group === 'all' || empty($group)) {
            return $query;
        }

        return $query->whereHas('employee', function ($eq) use ($group) {
            $eq->where(function ($sub) use ($group) {
                if ($group === 'fat') {
                    $sub->whereHas('assignments.department', fn ($d) => $d->where('name', 'like', '%FAT%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%'))
                        ->orWhereHas('assignments.division', fn ($d) => $d->where('name', 'like', '%FAT%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%'))
                        ->orWhereHas('department', fn ($d) => $d->where('name', 'like', '%FAT%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%'))
                        ->orWhereHas('division', fn ($d) => $d->where('name', 'like', '%FAT%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%'));
                } elseif ($group === 'hrga') {
                    $sub->whereHas('assignments.department', fn ($d) => $d->where('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%'))
                        ->orWhereHas('assignments.division', fn ($d) => $d->where('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%'))
                        ->orWhereHas('department', fn ($d) => $d->where('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%'))
                        ->orWhereHas('division', fn ($d) => $d->where('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%'));
                } elseif ($group === 'sales') {
                    $sub->whereHas('assignments.department', fn ($d) => $d->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%'))
                        ->orWhereHas('assignments.division', fn ($d) => $d->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%')->orWhere('name', 'like', '%Content%')->orWhere('name', 'like', '%Corporate Communication%'))
                        ->orWhereHas('department', fn ($d) => $d->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%'))
                        ->orWhereHas('division', fn ($d) => $d->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%')->orWhere('name', 'like', '%Content%')->orWhere('name', 'like', '%Corporate Communication%'));
                } elseif ($group === 'operasional' || $group === 'operational') {
                    $sub->where(function ($op) {
                        $op->whereHas('assignments.department', fn ($d) => $d->where('name', 'like', '%Operation%')->orWhere('name', 'like', '%Logistik%')->orWhere('name', 'like', '%Purchasing%'))
                           ->orWhereHas('assignments.division', fn ($d) => $d->where('name', 'like', '%Operation%')->orWhere('name', 'like', '%Gudang%')->orWhere('name', 'like', '%Bengkel%')->orWhere('name', 'like', '%Toko%')->orWhere('name', 'like', '%Logistik%')->orWhere('name', 'like', '%Inventory%'))
                           ->orWhereHas('department', fn ($d) => $d->where('name', 'like', '%Operation%')->orWhere('name', 'like', '%Logistik%')->orWhere('name', 'like', '%Purchasing%'))
                           ->orWhereHas('division', fn ($d) => $d->where('name', 'like', '%Operation%')->orWhere('name', 'like', '%Gudang%')->orWhere('name', 'like', '%Bengkel%')->orWhere('name', 'like', '%Toko%')->orWhere('name', 'like', '%Logistik%')->orWhere('name', 'like', '%Inventory%'));
                    })
                    ->whereDoesntHave('assignments.division', fn ($d) => $d->where('name', 'like', '%Content%')->orWhere('name', 'like', '%Corporate Communication%')->orWhere('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%')->orWhere('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%')->orWhere('name', 'like', '%FAT%'))
                    ->whereDoesntHave('division', fn ($d) => $d->where('name', 'like', '%Content%')->orWhere('name', 'like', '%Corporate Communication%')->orWhere('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%')->orWhere('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%')->orWhere('name', 'like', '%FAT%'))
                    ->whereDoesntHave('assignments.department', fn ($d) => $d->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%')->orWhere('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%')->orWhere('name', 'like', '%FAT%'))
                    ->whereDoesntHave('department', fn ($d) => $d->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%')->orWhere('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%')->orWhere('name', 'like', '%FAT%'));
                }
            });
        });
    }
}

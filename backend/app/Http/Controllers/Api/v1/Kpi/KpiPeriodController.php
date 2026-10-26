<?php

namespace App\Http\Controllers\Api\v1\Kpi;

use App\Http\Controllers\Controller;
use App\Models\KpiPeriod;
use App\Models\KpiAssessment;
use App\Models\KpiTemplate;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KpiPeriodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isNonAdmin = ($user instanceof Employee && !$user->isSuperAdmin() && !$user->isStaffHr());

        $query = KpiPeriod::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('start_date', 'desc');

        if ($isNonAdmin && $user instanceof Employee) {
            $subordinateIds = Employee::whereHas('assignments', fn ($aq) => $aq->where('supervisor_id', $user->id))
                ->pluck('id')
                ->toArray();

            $query->withCount([
                'assessments' => function ($q) use ($subordinateIds) {
                    $q->whereIn('employee_id', $subordinateIds);
                },
                'assessments as completed_assessments_count' => function ($q) use ($subordinateIds) {
                    $q->whereIn('employee_id', $subordinateIds)
                      ->whereIn('status', ['in_review', 'completed', 'approved']);
                }
            ]);
        } else {
            $query->withCount([
                'assessments',
                'assessments as completed_assessments_count' => function ($q) {
                    $q->whereIn('status', ['in_review', 'completed', 'approved']);
                }
            ]);
        }

        $items = $query->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $isNonAdmin = ($user instanceof Employee && !$user->isSuperAdmin() && !$user->isStaffHr());

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:daily,weekly,monthly,quarterly,semesterly,annual',
            'department_group' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'review_start_date' => 'nullable|date',
            'review_end_date' => 'nullable|date',
            'status' => 'sometimes|in:draft,active,closed',
            'notes' => 'nullable|string',
        ]);

        if ($isNonAdmin && empty($data['department_group'])) {
            $data['department_group'] = $user->department_group ?? 'Operasional';
        }

        $period = KpiPeriod::create($data);
        return response()->json(['success' => true, 'data' => $period], 201);
    }

    public function show(KpiPeriod $kpiPeriod): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $kpiPeriod->loadCount('assessments'),
        ]);
    }

    public function update(Request $request, KpiPeriod $kpiPeriod): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:daily,weekly,monthly,quarterly,semesterly,annual',
            'department_group' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'review_start_date' => 'nullable|date',
            'review_end_date' => 'nullable|date',
            'status' => 'sometimes|in:draft,active,closed',
            'notes' => 'nullable|string',
        ]);

        $kpiPeriod->update($data);
        return response()->json(['success' => true, 'data' => $kpiPeriod]);
    }

    public function destroy(KpiPeriod $kpiPeriod): JsonResponse
    {
        $kpiPeriod->delete();
        return response()->json(['success' => true, 'message' => 'Periode berhasil dihapus']);
    }

    /**
     * Generate assessment untuk karyawan aktif di periode ini.
     * Dapat difilter per departemen (all, fat, hrga, sales, operasional).
     * Reviewer otomatis = supervisor langsung (employee.supervisor_id).
     * Untuk Head/SPV, otomatis difokuskan ke anak buahnya.
     */
    public function generateAssessments(Request $request, KpiPeriod $kpiPeriod): JsonResponse
    {
        $user = $request->user();
        $isNonAdmin = ($user instanceof Employee && !$user->isSuperAdmin() && !$user->isStaffHr());

        $deptGroup = strtolower($request->input('department_group', $kpiPeriod->department_group ?? 'all'));

        $query = Employee::with(['primaryAssignment.department', 'primaryAssignment.division', 'primaryAssignment.position', 'position', 'division'])
            ->where('is_active', true);

        // Jika dijalankan oleh Head / SPV, generate difokuskan ke anak buah langsung & divisi kelolaan
        if ($isNonAdmin || $request->boolean('only_subordinates')) {
            $managedDivs = ($user instanceof Employee && is_array($user->managed_division_ids)) ? $user->managed_division_ids : [];
            $query->where(function ($q) use ($user, $managedDivs) {
                $q->whereHas('assignments', fn ($aq) => $aq->where('supervisor_id', $user->id));
                if (!empty($managedDivs)) {
                    $q->orWhereHas('assignments', fn ($aq) => $aq->whereIn('division_id', $managedDivs));
                }
            });
        } elseif ($deptGroup && $deptGroup !== 'all') {
            $query->where(function ($q) use ($deptGroup) {
                if ($deptGroup === 'fat') {
                    $q->whereHas('assignments.department', fn ($d) => $d->where('name', 'like', '%FAT%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%'))
                      ->orWhereHas('assignments.division', fn ($d) => $d->where('name', 'like', '%FAT%')->orWhere('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%Tax%'));
                } elseif ($deptGroup === 'hrga') {
                    $q->whereHas('assignments.department', fn ($d) => $d->where('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%'))
                      ->orWhereHas('assignments.division', fn ($d) => $d->where('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%'));
                } elseif ($deptGroup === 'sales') {
                    $q->whereHas('assignments.department', fn ($d) => $d->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%'))
                      ->orWhereHas('assignments.division', fn ($d) => $d->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%')->orWhere('name', 'like', '%Content%')->orWhere('name', 'like', '%Corporate Communication%'));
                } elseif ($deptGroup === 'operasional' || $deptGroup === 'operational') {
                    $q->whereHas('assignments.department', fn ($d) => $d->where('name', 'like', '%Operation%')->orWhere('name', 'like', '%Logistik%')->orWhere('name', 'like', '%Purchasing%'))
                      ->orWhereHas('assignments.division', fn ($d) => $d->where('name', 'like', '%Operation%')->orWhere('name', 'like', '%Gudang%')->orWhere('name', 'like', '%Bengkel%')->orWhere('name', 'like', '%Toko%')->orWhere('name', 'like', '%Logistik%')->orWhere('name', 'like', '%Inventory%'));
                }
            });
        }

        $employees = $query->get();

        $generated = 0;
        $skipped   = 0;
        $noTemplateCount = 0;
        $alreadyExistsCount = 0;
        $deptCounts = ['FAT' => 0, 'HRGA' => 0, 'Sales' => 0, 'Operasional' => 0];

        foreach ($employees as $employee) {
            $assignment   = $employee->primaryAssignment ?? $employee->assignments()->first();
            $positionId   = $assignment?->position_id ?? $employee->position_id;
            $divisionId   = $assignment?->division_id ?? $employee->division_id;
            $supervisorId = $assignment?->supervisor_id ?? $employee->supervisor_id;
            $posName      = strtolower($assignment?->position?->name ?? $employee->position?->name ?? '');
            $empDeptGroup = $employee->department_group;

            // 1. Cek template berdasar Jabatan langsung
            $template = null;
            if ($positionId) {
                $template = KpiTemplate::where('is_active', true)
                    ->where('scope', 'position')
                    ->where('position_id', $positionId)
                    ->first();
            }

            // 2. Cek template berdasar Divisi
            if (!$template && $divisionId) {
                $template = KpiTemplate::where('is_active', true)
                    ->where('scope', 'division')
                    ->where('division_id', $divisionId)
                    ->first();
            }

            // 3. Cek kemiripan nama posisi dengan nama template
            if (!$template && !empty($posName)) {
                $template = KpiTemplate::where('is_active', true)
                    ->where(function ($tq) use ($posName) {
                        $tq->where('name', 'like', "%{$posName}%")
                           ->orWhereHas('position', fn ($pq) => $pq->where('name', 'like', "%{$posName}%"));
                    })
                    ->first();
            }

            // 4. Fallback: Cari template aktif yang sesuai dengan grup departemen karyawan
            if (!$template) {
                $template = KpiTemplate::where('is_active', true)
                    ->where(function ($tq) use ($empDeptGroup) {
                        if ($empDeptGroup === 'FAT') {
                            $tq->where('name', 'like', '%Finance%')->orWhere('name', 'like', '%Accounting%')->orWhere('name', 'like', '%FAT%');
                        } elseif ($empDeptGroup === 'HRGA') {
                            $tq->where('name', 'like', '%HR%')->orWhere('name', 'like', '%GA%');
                        } elseif ($empDeptGroup === 'Sales') {
                            $tq->where('name', 'like', '%Sales%')->orWhere('name', 'like', '%Marketing%');
                        } else {
                            $tq->where('name', 'like', '%Operasional%')->orWhere('name', 'like', '%Gudang%')->orWhere('name', 'like', '%Bengkel%');
                        }
                    })
                    ->first();
            }

            // 5. Ultimate Fallback
            if (!$template) {
                $template = KpiTemplate::where('is_active', true)->first();
            }

            if (!$template) {
                $noTemplateCount++;
                $skipped++;
                continue;
            }

            // 6. Cek jika sudah ada assessment untuk periode + karyawan ini
            $exists = KpiAssessment::where('period_id', $kpiPeriod->id)
                ->where('employee_id', $employee->id)
                ->exists();

            if ($exists) {
                $alreadyExistsCount++;
                $skipped++;
                continue;
            }

            $finalReviewerId = ($isNonAdmin && $user instanceof Employee) 
                ? $user->id 
                : ($supervisorId ?: ($user instanceof Employee ? $user->id : null));

            KpiAssessment::create([
                'period_id'   => $kpiPeriod->id,
                'employee_id' => $employee->id,
                'template_id' => $template->id,
                'reviewer_id' => $finalReviewerId,
                'status'      => 'pending',
            ]);

            $generated++;
            if (isset($deptCounts[$empDeptGroup])) {
                $deptCounts[$empDeptGroup]++;
            }
        }

        $message = "Generate Assessment: {$generated} dibuat, {$skipped} dilewati.";
        if ($generated > 0) {
            $breakdown = [];
            foreach ($deptCounts as $grp => $cnt) {
                if ($cnt > 0) $breakdown[] = "{$grp}: {$cnt}";
            }
            if (!empty($breakdown)) {
                $message .= " (" . implode(', ', $breakdown) . ")";
            }
        }
        if ($noTemplateCount > 0) {
            $message .= " ({$noTemplateCount} karyawan tidak menemukan template KPI aktif).";
        }
        if ($alreadyExistsCount > 0) {
            $message .= " ({$alreadyExistsCount} karyawan sudah memiliki assessment di periode ini).";
        }

        return response()->json([
            'success'     => true,
            'message'     => $message,
            'generated'   => $generated,
            'skipped'     => $skipped,
            'dept_counts' => $deptCounts,
        ]);
    }
}

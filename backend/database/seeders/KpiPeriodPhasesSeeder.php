<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\KpiAssessment;
use App\Models\Employee;
use Carbon\Carbon;

class KpiPeriodPhasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::now();

        $periodsConfig = [
            [
                'name' => 'Penilaian KPI Harian (Daily)',
                'type' => 'daily',
                'start_date' => $today->toDateString(),
                'end_date' => $today->toDateString(),
                'review_start_date' => $today->toDateString(),
                'review_end_date' => $today->copy()->addDay()->toDateString(),
                'status' => 'active',
                'department_group' => 'all',
                'notes' => 'Fase evaluasi KPI harian untuk memonitor capaian operasional dan produktivitas harian karyawan.',
            ],
            [
                'name' => 'Penilaian KPI Mingguan (W3 Oktober 2026)',
                'type' => 'weekly',
                'start_date' => $today->copy()->startOfWeek()->toDateString(),
                'end_date' => $today->copy()->endOfWeek()->toDateString(),
                'review_start_date' => $today->copy()->endOfWeek()->subDay()->toDateString(),
                'review_end_date' => $today->copy()->endOfWeek()->addDays(2)->toDateString(),
                'status' => 'active',
                'department_group' => 'all',
                'notes' => 'Fase evaluasi KPI mingguan untuk evaluasi target sprint, omzet mingguan, dan kehadiran tim.',
            ],
            [
                'name' => 'Penilaian KPI Bulanan (Oktober 2026)',
                'type' => 'monthly',
                'start_date' => $today->copy()->startOfMonth()->toDateString(),
                'end_date' => $today->copy()->endOfMonth()->toDateString(),
                'review_start_date' => $today->copy()->endOfMonth()->subDays(5)->toDateString(),
                'review_end_date' => $today->copy()->endOfMonth()->addDays(7)->toDateString(),
                'status' => 'active',
                'department_group' => 'all',
                'notes' => 'Fase evaluasi KPI bulanan reguler untuk seluruh divisi (FAT, HRGA, Sales, Operasional).',
            ],
            [
                'name' => 'Penilaian KPI Kuartalan (Q4 2026)',
                'type' => 'quarterly',
                'start_date' => $today->copy()->firstOfQuarter()->toDateString(),
                'end_date' => $today->copy()->lastOfQuarter()->toDateString(),
                'review_start_date' => $today->copy()->lastOfQuarter()->subDays(10)->toDateString(),
                'review_end_date' => $today->copy()->lastOfQuarter()->addDays(15)->toDateString(),
                'status' => 'active',
                'department_group' => 'all',
                'notes' => 'Fase evaluasi KPI kuartalan untuk penilaian performa strategis, OKR, dan review kenaikan berkala.',
            ],
        ];

        $createdPeriods = [];
        foreach ($periodsConfig as $cfg) {
            $period = KpiPeriod::updateOrCreate(
                ['name' => $cfg['name']],
                $cfg
            );
            $createdPeriods[] = $period;
        }

        // Clean up or update the old draft 'P1' if it exists and has no assessments
        $p1 = KpiPeriod::where('name', 'P1')->first();
        if ($p1 && KpiAssessment::where('period_id', $p1->id)->count() === 0) {
            $p1->delete();
        }

        // Generate assessments for active Monthly and Weekly periods
        $employees = Employee::with([
            'primaryAssignment.department',
            'primaryAssignment.division',
            'primaryAssignment.position',
            'position',
            'division'
        ])->where('is_active', true)->get();

        foreach ($createdPeriods as $period) {
            foreach ($employees as $employee) {
                $assignment   = $employee->primaryAssignment ?? $employee->assignments()->first();
                $positionId   = $assignment?->position_id ?? $employee->position_id;
                $divisionId   = $assignment?->division_id ?? $employee->division_id;
                $supervisorId = $assignment?->supervisor_id ?? $employee->supervisor_id;
                $posName      = strtolower($assignment?->position?->name ?? $employee->position?->name ?? '');
                $empDeptGroup = $employee->department_group;

                // Find best matching template
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

                if (!$template && !empty($posName)) {
                    $template = KpiTemplate::where('is_active', true)
                        ->where(function ($tq) use ($posName) {
                            $tq->where('name', 'like', "%{$posName}%")
                               ->orWhereHas('position', fn ($pq) => $pq->where('name', 'like', "%{$posName}%"));
                        })
                        ->first();
                }

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

                if (!$template) {
                    $template = KpiTemplate::where('is_active', true)->first();
                }

                if (!$template) continue;

                // Check if assessment already exists
                $assessment = KpiAssessment::firstOrCreate(
                    [
                        'period_id'   => $period->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'template_id' => $template->id,
                        'reviewer_id' => $supervisorId,
                        'status'      => 'pending',
                    ]
                );
            }
        }
    }
}

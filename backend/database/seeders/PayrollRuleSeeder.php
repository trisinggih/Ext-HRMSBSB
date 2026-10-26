<?php

namespace Database\Seeders;

use App\Models\PayrollComponent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $components = PayrollComponent::pluck('id', 'code');

        if ($components->isEmpty()) {
            return;
        }

        $rules = [
            // 1. Tunjangan Transport - Dihitung berdasarkan jumlah kehadiran
            [
                'payroll_component_id' => $components['tunjangan_transport'] ?? null,
                'code' => 'R_TRANSPORT_ATTENDANCE',
                'name' => 'Tunjangan Transport per Kehadiran',
                'source' => 'ATTENDANCE',
                'method' => 'PER_DAY',
                'condition' => '{"type": "always"}', // Selalu berlaku
                'formula' => '{nominal} * {total_attendance_days}',
                'priority' => 10,
                'is_active' => true,
            ],
            // 2. Tunjangan Makan - Dihitung berdasarkan jumlah kehadiran
            [
                'payroll_component_id' => $components['tunjangan_makan'] ?? null,
                'code' => 'R_MEAL_ATTENDANCE',
                'name' => 'Tunjangan Makan per Kehadiran',
                'source' => 'ATTENDANCE',
                'method' => 'PER_DAY',
                'condition' => '{"type": "always"}',
                'formula' => '{nominal} * {total_attendance_days}',
                'priority' => 10,
                'is_active' => true,
            ],
            // 3. Uang Lembur - Berdasarkan total jam lembur yang disetujui dikali nominal per jam
            [
                'payroll_component_id' => $components['uang_lembur'] ?? null,
                'code' => 'R_OVERTIME_HOURS',
                'name' => 'Uang Lembur per Jam',
                'source' => 'OVERTIME',
                'method' => 'PER_HOUR',
                'condition' => '{"type": "always"}',
                'formula' => '{nominal} * {total_overtime_hours}',
                'priority' => 10,
                'is_active' => true,
            ],
            // 4. BPJS Kesehatan (1%) - Dipotong dari basis BPJS (biasanya Gaji Pokok + Tunjangan Tetap)
            [
                'payroll_component_id' => $components['bpjs_kes_karyawan'] ?? null,
                'code' => 'R_BPJS_KES_1_PCT',
                'name' => 'Potongan BPJS Kesehatan 1%',
                'source' => 'FORMULA',
                'method' => 'PERCENTAGE',
                'condition' => '{"type": "always"}',
                'formula' => 'MIN({bpjs_basis_total}, 12000000) * 0.01', // Max basis BPJS Kesehatan 12jt
                'priority' => 50,
                'is_active' => true,
            ],
            // 5. BPJS Ketenagakerjaan JHT (2%) - Dipotong dari basis BPJS
            [
                'payroll_component_id' => $components['bpjs_tk_jht_karyawan'] ?? null,
                'code' => 'R_BPJS_JHT_2_PCT',
                'name' => 'Potongan BPJS JHT 2%',
                'source' => 'FORMULA',
                'method' => 'PERCENTAGE',
                'condition' => '{"type": "always"}',
                'formula' => '{bpjs_basis_total} * 0.02', // Tidak ada batas maksimal untuk JHT
                'priority' => 50,
                'is_active' => true,
            ],
            // 6. BPJS Ketenagakerjaan JP (1%) - Dipotong dari basis BPJS
            [
                'payroll_component_id' => $components['bpjs_tk_jp_karyawan'] ?? null,
                'code' => 'R_BPJS_JP_1_PCT',
                'name' => 'Potongan BPJS JP 1%',
                'source' => 'FORMULA',
                'method' => 'PERCENTAGE',
                'condition' => '{"type": "always"}',
                'formula' => 'MIN({bpjs_basis_total}, 10042300) * 0.01', // Max basis JP 10jt an (2024)
                'priority' => 50,
                'is_active' => true,
            ],
            // 7. Potongan Keterlambatan - Denda keterlambatan per kejadian
            [
                'payroll_component_id' => $components['potongan_terlambat'] ?? null,
                'code' => 'R_LATE_DEDUCTION',
                'name' => 'Potongan Terlambat per Kejadian',
                'source' => 'ATTENDANCE',
                'method' => 'PER_OCCURRENCE',
                'condition' => '{"type": "always"}',
                'formula' => '{nominal} * {total_late_occurrences}',
                'priority' => 20,
                'is_active' => true,
            ],
            // 8. Potongan Absen (Unpaid Leave) - Dipotong dari gaji per hari absen
            [
                'payroll_component_id' => $components['potongan_absen'] ?? null,
                'code' => 'R_UNPAID_LEAVE_DEDUCTION',
                'name' => 'Potongan Tidak Masuk / Unpaid Leave',
                'source' => 'ATTENDANCE',
                'method' => 'PER_DAY',
                'condition' => '{"type": "always"}',
                'formula' => '{nominal} * {total_unpaid_leave_days}',
                'priority' => 20,
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            if (!$rule['payroll_component_id']) continue;

            DB::table('payroll_rules')->updateOrInsert(
                ['code' => $rule['code']],
                array_merge($rule, [
                    'effective_from' => '2026-01-01',
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}

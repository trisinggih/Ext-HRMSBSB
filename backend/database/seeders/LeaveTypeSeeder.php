<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Cuti Tahunan',
                'code' => 'CT',
                'category' => 'annual',
                'default_days' => 12,
                'is_paid' => true,
                'is_carry_forward' => true,
                'max_carry_forward_days' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Sakit',
                'code' => 'CS',
                'category' => 'special',
                'default_days' => 0, // 0 = unlimited / depends on doctor's note
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Melahirkan',
                'code' => 'CM',
                'category' => 'maternity',
                'default_days' => 90, // 1.5 months before + 1.5 months after
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Keguguran',
                'code' => 'CK',
                'category' => 'special',
                'default_days' => 45, // 1.5 months
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Menikah (Karyawan)',
                'code' => 'CMK',
                'category' => 'marriage',
                'default_days' => 3,
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Menikahkan Anak',
                'code' => 'CMA',
                'category' => 'special',
                'default_days' => 2,
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Khitanan/Pembaptisan Anak',
                'code' => 'CPA',
                'category' => 'special',
                'default_days' => 2,
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Istri Melahirkan/Keguguran',
                'code' => 'CIM',
                'category' => 'special',
                'default_days' => 2,
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Anggota Keluarga Meninggal',
                'code' => 'CDK',
                'category' => 'special',
                'default_days' => 2, // Suami/Istri, Orang Tua/Mertua, Anak/Menantu
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Haid',
                'code' => 'CH',
                'category' => 'special',
                'default_days' => 2,
                'is_paid' => true,
                'is_carry_forward' => false,
                'max_carry_forward_days' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            DB::table('leave_types')->updateOrInsert(
                ['code' => $leaveType['code']],
                array_merge($leaveType, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}

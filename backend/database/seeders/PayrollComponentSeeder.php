<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $components = [
            // EARNINGS
            [
                'code' => 'gaji_pokok',
                'name' => 'Gaji Pokok',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 10,
                'calculation_source' => 'FIXED',
                'calculation_method' => 'FIXED_AMOUNT',
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => true,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'tunjangan_jabatan',
                'name' => 'Tunjangan Jabatan',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 20,
                'calculation_source' => 'FIXED',
                'calculation_method' => 'FIXED_AMOUNT',
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => true,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'tunjangan_transport',
                'name' => 'Tunjangan Transport',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 30,
                'calculation_source' => 'ATTENDANCE', // based on attendance
                'calculation_method' => 'PER_DAY',
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'tunjangan_makan',
                'name' => 'Tunjangan Makan',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 40,
                'calculation_source' => 'ATTENDANCE',
                'calculation_method' => 'PER_DAY',
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'uang_lembur',
                'name' => 'Uang Lembur (Overtime)',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 50,
                'calculation_source' => 'OVERTIME',
                'calculation_method' => 'PER_HOUR',
                'is_active' => false,
                'is_taxable' => true,
                'is_bpjs_basis' => false,
                'show_on_payslip' => false,
            ],
            [
                'code' => 'uang_lembur_20k',
                'name' => 'Uang Lembur (20rb/Jam)',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 52,
                'calculation_source' => 'FORMULA',
                'calculation_method' => 'FORMULA',
                'default_value' => 20000,
                'has_rules' => true,
                'formula' => '20000 * {total_overtime_hours}',
                'payment_schema' => [
                    'MONTHLY_PAID_MONTHLY',
                    'DAILY_PAID_MONTHLY',
                    'DAILY_PAID_WEEKLY',
                    'DAILY_PAID_DAILY'
                ],
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'thr_bonus',
                'name' => 'THR / Bonus Tahunan',
                'type' => 'earning',
                'category' => 'annual',
                'sort_order' => 60,
                'calculation_source' => 'MANUAL',
                'calculation_method' => 'FIXED_AMOUNT',
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],

            // DEDUCTIONS
            [
                'code' => 'potongan_terlambat',
                'name' => 'Potongan Keterlambatan',
                'type' => 'deduction',
                'category' => 'monthly',
                'sort_order' => 110,
                'calculation_source' => 'ATTENDANCE',
                'calculation_method' => 'PER_OCCURRENCE',
                'is_active' => true,
                'is_taxable' => false,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'potongan_absen',
                'name' => 'Potongan Tidak Masuk (Unpaid Leave)',
                'type' => 'deduction',
                'category' => 'monthly',
                'sort_order' => 120,
                'calculation_source' => 'ATTENDANCE',
                'calculation_method' => 'PER_DAY',
                'is_active' => true,
                'is_taxable' => false,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'bpjs_kes_karyawan',
                'name' => 'BPJS Kesehatan (1%)',
                'type' => 'deduction',
                'category' => 'monthly',
                'sort_order' => 130,
                'calculation_source' => 'FORMULA',
                'calculation_method' => 'PERCENTAGE',
                'is_active' => true,
                'is_taxable' => false,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'bpjs_tk_jht_karyawan',
                'name' => 'BPJS TK - JHT (2%)',
                'type' => 'deduction',
                'category' => 'monthly',
                'sort_order' => 140,
                'calculation_source' => 'FORMULA',
                'calculation_method' => 'PERCENTAGE',
                'is_active' => true,
                'is_taxable' => false, // Reduces taxable income
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'bpjs_tk_jp_karyawan',
                'name' => 'BPJS TK - JP (1%)',
                'type' => 'deduction',
                'category' => 'monthly',
                'sort_order' => 150,
                'calculation_source' => 'FORMULA',
                'calculation_method' => 'PERCENTAGE',
                'is_active' => true,
                'is_taxable' => false, // Reduces taxable income
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'potongan_kasbon',
                'name' => 'Cicilan Pinjaman / Kasbon',
                'type' => 'deduction',
                'category' => 'loan',
                'sort_order' => 160,
                'calculation_source' => 'LOAN',
                'calculation_method' => 'SCHEDULED_INSTALLMENT',
                'is_active' => true,
                'is_taxable' => false,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],
            [
                'code' => 'pph21',
                'name' => 'Pajak PPh 21',
                'type' => 'deduction',
                'category' => 'monthly',
                'sort_order' => 190, // usually at the end
                'calculation_source' => 'TAX',
                'calculation_method' => 'FORMULA',
                'is_active' => true,
                'is_taxable' => false,
                'is_bpjs_basis' => false,
                'show_on_payslip' => true,
            ],

            // BENEFITS (Paid by company, adds to taxable income but not take-home pay)
            [
                'code' => 'bpjs_kes_perusahaan',
                'name' => 'Tunjangan BPJS Kesehatan (4%)',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 210,
                'calculation_source' => 'FORMULA',
                'calculation_method' => 'PERCENTAGE',
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => false,
                'show_on_payslip' => false,
            ],
            [
                'code' => 'bpjs_tk_jkk_perusahaan',
                'name' => 'Tunjangan BPJS TK - JKK (0.24%)',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 220,
                'calculation_source' => 'FORMULA',
                'calculation_method' => 'PERCENTAGE',
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => false,
                'show_on_payslip' => false,
            ],
            [
                'code' => 'bpjs_tk_jkm_perusahaan',
                'name' => 'Tunjangan BPJS TK - JKM (0.3%)',
                'type' => 'earning',
                'category' => 'monthly',
                'sort_order' => 230,
                'calculation_source' => 'FORMULA',
                'calculation_method' => 'PERCENTAGE',
                'is_active' => true,
                'is_taxable' => true,
                'is_bpjs_basis' => false,
                'show_on_payslip' => false,
            ]
        ];

        foreach ($components as $component) {
            DB::table('payroll_components')->updateOrInsert(
                ['code' => $component['code']],
                array_merge($component, [
                    'effective_from' => '2026-01-01',
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}

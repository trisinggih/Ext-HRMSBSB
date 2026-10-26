<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PayrollVariableController extends Controller
{
    /**
     * Mengembalikan daftar variabel dinamis yang didukung oleh PayrollCalculatorService.
     */
    public function index(): JsonResponse
    {
        $variables = [
            [
                'code' => '{basic_salary}',
                'name' => 'Gaji Pokok',
                'description' => 'Nilai Gaji Pokok karyawan yang terdaftar.',
            ],
            [
                'code' => '{total_allowance}',
                'name' => 'Total Tunjangan',
                'description' => 'Jumlah keseluruhan tunjangan.',
            ],
            [
                'code' => '{taxable_earnings}',
                'name' => 'Pendapatan Kena Pajak',
                'description' => 'Total pendapatan yang menjadi objek pemotongan pajak.',
            ],
            [
                'code' => '{bpjs_basis_total}',
                'name' => 'Dasar Perhitungan BPJS',
                'description' => 'Total Gaji Pokok + Tunjangan Tetap sebagai dasar perhitungan BPJS.',
            ],
            [
                'code' => '{total_attendance_days}',
                'name' => 'Total Hari Kehadiran',
                'description' => 'Jumlah hari hadir (termasuk terlambat/pulang cepat).',
            ],
            [
                'code' => '{total_unpaid_leave_days}',
                'name' => 'Total Hari Absen',
                'description' => 'Jumlah hari tidak masuk tanpa dibayar (unpaid).',
            ],
            [
                'code' => '{total_overtime_hours}',
                'name' => 'Total Jam Lembur',
                'description' => 'Jumlah jam lembur yang disetujui.',
            ],
            [
                'code' => '{total_late_minutes}',
                'name' => 'Total Menit Terlambat',
                'description' => 'Akumulasi waktu keterlambatan dalam menit.',
            ],
            [
                'code' => '{total_late_occurrences}',
                'name' => 'Total Kejadian Terlambat',
                'description' => 'Frekuensi atau jumlah kali pegawai datang terlambat.',
            ],
            [
                'code' => '{total_late_deduction}',
                'name' => 'Total Potongan Keterlambatan',
                'description' => 'Akumulasi nominal potongan keterlambatan berjenjang (1-15m: 10rb, 16-30m: 20rb, >30m: 50rb per kehadiran).',
            ],
            [
                'code' => '{total_day_off}',
                'name' => 'Total Hari Libur yang Diambil',
                'description' => 'Jumlah hari libur (day_off) yang benar-benar diambil/digunakan oleh karyawan.',
            ],
            [
                'code' => '{is_probation}',
                'name' => 'Status Percobaan (Probation)',
                'description' => 'Bernilai 1 jika status karyawan adalah masa percobaan (probation), dan 0 jika tetap/kontrak.',
            ],
            [
                'code' => '{total_days_in_month}',
                'name' => 'Total Hari dalam Sebulan',
                'description' => 'Jumlah keseluruhan hari pada bulan penggajian berjalan (28, 29, 30, atau 31).',
            ],
            [
                'code' => '{amount}',
                'name' => 'Nominal Karyawan (Amount)',
                'description' => 'Nominal (amount) spesifik yang di-setting pada profil karyawan untuk komponen ini.',
            ],
            [
                'code' => '{total_working_hour_per_day}',
                'name' => 'Total Jam Kerja Per Hari',
                'description' => 'Jumlah jam kerja per hari (contoh: 8 jam).',
            ],
        ];

        // Ambil data komponen gaji yang aktif (menjadi variabel juga)
        $components = \App\Models\PayrollComponent::where('is_active', true)->get();
        foreach ($components as $comp) {
            $variables[] = [
                'code' => '{' . $comp->code . '}',
                'name' => 'Komponen: ' . $comp->name,
                'description' => 'Nilai dari komponen gaji ' . $comp->name,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar variabel dinamis',
            'data' => $variables,
        ]);
    }
}

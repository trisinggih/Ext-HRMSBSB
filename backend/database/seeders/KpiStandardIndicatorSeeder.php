<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Position;
use App\Models\KpiRatingScale;
use App\Models\KpiIndicator;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateIndicator;
use Illuminate\Support\Str;

class KpiStandardIndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $ratingScale = KpiRatingScale::firstOrCreate(
            ['name' => 'Skala Persentase (0-100)'],
            [
                'description' => 'Penilaian berdasarkan persentase pencapaian (0 - 100)',
                'is_active' => true,
            ]
        );

        $data = [
            // ==========================================
            // SALES & MARKETING
            // ==========================================
            [
                'dept' => 'Sales & Marketing',
                'position' => 'SALES & MARKETING MANAGER',
                'aliases' => ['Sales & Marketing Manager', 'Manager Sales & Marketing'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Pencapaian Omzet Total Jangeby Group & Cabang',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Realisasi Omzet ÷ Target Omzet x 100%',
                        'target' => '100% dari Target Omzet Group',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Kontribusi Sales Online & Cabang Peby Motor',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Omzet Kanal ÷ Total Omzet x 100%',
                        'target' => 'Sesuai target kontribusi kanal',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Efektivitas Eksekusi Kampanye & Promo',
                        'weight' => 15,
                        'frequency' => 'M',
                        'formula' => 'Program terlaksana tepat waktu ÷ Rencana x 100%',
                        'target' => '100% program terlaksana tepat waktu',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Retensi & Produktivitas Tim Sales & Corcom',
                        'weight' => 20,
                        'frequency' => 'K',
                        'formula' => 'Anggota capai target ÷ Total anggota x 100%',
                        'target' => '≥85% anggota tim capai target',
                        'unit' => '%',
                    ],
                    [
                        'no' => 5,
                        'name' => 'ROAS & Efisiensi Budget Marketing/Ads',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Omzet Ads ÷ Total Spend x 100%',
                        'target' => 'ROAS tercapai sesuai standar alokasi budget',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'SALES SUPERVISOR',
                'aliases' => ['Sales Spv', 'Sales Supervisor'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Pencapaian Target Omzet Tim Sales',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Omzet Tim ÷ Target Tim x 100%',
                        'target' => '100% dari Target Omzet Tim',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Distribusi Pencapaian Sales Executive',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Sales capai ≥90% target ÷ Total sales x 100%',
                        'target' => '≥80% sales capai target ≥90%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Aktivitas Penawaran & Proposal Terkirim',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Proposal terkirim aktual ÷ Rencana x 100%',
                        'target' => '100% proposal terkirim sesuai target',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Coaching & 1-on-1 Session Terlaksana',
                        'weight' => 15,
                        'frequency' => 'B',
                        'formula' => 'Sesi terlaksana ÷ Jadwal x 100%',
                        'target' => '100% sesi terlaksana sesuai jadwal',
                        'unit' => '%',
                    ],
                    [
                        'no' => 5,
                        'name' => 'Piutang Penjualan & Tagihan Terkendali',
                        'weight' => 15,
                        'frequency' => 'B',
                        'formula' => 'Piutang overdue ≤5% dari total piutang tim',
                        'target' => 'Piutang overdue ≤5% dari total piutang tim',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'SALES EXECUTIVE',
                'aliases' => ['Sales Executive'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Nilai Closing / Omzet Baru',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Total nilai kontrak/PO ditandatangani',
                        'target' => '100% target closing pribadi tercapai',
                        'unit' => 'Rp',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Conversion Rate Prospek ke Closing',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Closing ÷ Prospek aktif x 100%',
                        'target' => '≥20% rasio konversi prospek ke closing',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Aktivitas Penawaran / Proposal',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Jumlah proposal terkirim per bulan',
                        'target' => 'Memenuhi kuota proposal bulanan',
                        'unit' => 'Jumlah',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Disiplin Update CRM / Laporan',
                        'weight' => 15,
                        'frequency' => 'M',
                        'formula' => 'Laporan lengkap tepat waktu ÷ Total x 100%',
                        'target' => '100% laporan update tepat waktu',
                        'unit' => '%',
                    ],
                    [
                        'no' => 5,
                        'name' => 'Repeat Order Klien Baru',
                        'weight' => 10,
                        'frequency' => 'K',
                        'formula' => 'Klien repeat ÷ Klien baru tahun lalu x 100%',
                        'target' => '≥30% repeat order klien baru',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'CORPORATE COMMUNICATION',
                'aliases' => ['Corporate Communication SPV', 'Corporate Communication Staff', 'Corporate Communication'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Brand Mention & Sentimen Positif',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Total mention positif ÷ Total mention x 100%',
                        'target' => '≥85% sentimen positif brand',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Rilis Pers / Liputan Media',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Jumlah siaran pers / liputan terpublikasi',
                        'target' => 'Target rilis/liputan media terpenuhi',
                        'unit' => 'Jumlah',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Respons Krisis & Isu Publik',
                        'weight' => 25,
                        'frequency' => 'I',
                        'formula' => 'Isu terselesaikan ≤24 jam tanpa eskalasi',
                        'target' => 'Isu terselesaikan ≤24 jam tanpa eskalasi',
                        'unit' => 'Jam',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Keterlibatan Pemangku Kepentingan (Stakeholders)',
                        'weight' => 20,
                        'frequency' => 'K',
                        'formula' => 'Program CSR / audiensi komunitas terealisasi',
                        'target' => '100% program CSR & audiensi terealisasi',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'COMMUNITY DEVELOPMENT',
                'aliases' => ['Community Development SPV', 'Community Development'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Pertumbuhan Anggota Komunitas',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Jumlah anggota baru bergabung vs target',
                        'target' => '100% target pertumbuhan anggota baru',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Aktivitas & Event Komunitas',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Event terealisasi ÷ Rencana event x 100%',
                        'target' => '100% event komunitas terealisasi',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Engagement Rate Komunitas',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Interaksi aktif anggota per bulan',
                        'target' => 'Memenuhi target rasio interaksi bulanan',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Konversi Komunitas ke Pembelian',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Transaksi berasal dari komunitas ÷ Target x 100%',
                        'target' => '100% target transaksi dari komunitas tercapai',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'CONTENT & BRANDING INTERNAL JANGEBY',
                'aliases' => ['Content & Branding Staff', 'Content & Branding Internal Jangeby'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Konsistensi Output Konten Internal',
                        'weight' => 30,
                        'frequency' => 'M',
                        'formula' => 'Konten selesai tepat jadwal ÷ Rencana x 100%',
                        'target' => '100% konten selesai tepat jadwal',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Engagement Media Sosial Pusat',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Total interaksi (like/share/comment) ÷ Followers x 100%',
                        'target' => 'Tercapai target engagement rate mingguan',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Brand Guideline Compliance',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Aset branding sesuai standar visual ≥95%',
                        'target' => 'Aset branding sesuai standar visual ≥95%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Waktu Produksi Aset Visual',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Rata-rata durasi pembuatan materi promosi',
                        'target' => 'Sesuai SLA waktu produksi (≤ deadline)',
                        'unit' => 'Hari',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'DIGITAL MARKETING LEADER',
                'aliases' => ['Digital Marketing SPV', 'Digital Marketing Leader'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Pencapaian Omzet Digital / Leads',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Omzet/Leads via kanal digital vs target',
                        'target' => '100% target omzet/leads digital tercapai',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'ROAS Kampanye Digital',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Total pendapatan iklan ÷ Biaya iklan x 100%',
                        'target' => 'ROAS ≥ target efisiensi iklan',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Cost Per Acquisition (CPA) / CPL',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Total spend ÷ Jumlah konversi baru',
                        'target' => 'CPA/CPL di bawah batas maksimal anggaran',
                        'unit' => 'Rp',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Tingkat Pertumbuhan Kanal Digital (Followers/Traffic)',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Pertumbuhan traffic web/sosmed vs target bulanan',
                        'target' => '100% target pertumbuhan followers/traffic tercapai',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'HEAD HOST LIVE',
                'aliases' => ['Host Live Leader', 'Head Host Live', 'Host Live'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Pencapaian GMV / Sales Live Shopping',
                        'weight' => 35,
                        'frequency' => 'B',
                        'formula' => 'Realisasi GMV live ÷ Target GMV live x 100%',
                        'target' => '100% target GMV live tercapai',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Viewer Retensi & Jam Tayang Live',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Total durasi tayang & rata-rata penonton serentak',
                        'target' => 'Memenuhi kuota durasi tayang & retensi penonton',
                        'unit' => 'Jam',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Kesiapan Host & Jadwal Siaran',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Jadwal live terealisasi 100% tanpa delay',
                        'target' => 'Jadwal live terealisasi 100% tanpa delay',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Conversion Rate Live ke Checkout',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Order terbayar live ÷ Total checkout x 100%',
                        'target' => 'Rasio checkout terbayar sesuai target',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'KEPALA DIGITAL CREATIVE',
                'aliases' => ['Kepala Digital Creative'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Kreativitas & Konsep Kampanye Lolos Review',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Ide kreatif disetujui tanpa revisi mayor ≥85%',
                        'target' => 'Ide kreatif disetujui tanpa revisi mayor ≥85%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Efektivitas Konten Viral / High-Performing',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Konten mencapai target views/engagement',
                        'target' => 'Mencapai KPI target views & interaksi',
                        'unit' => 'Views',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Manajemen Waktu Eksekusi Tim Kreatif',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Penyelesaian aset kreatif tepat deadline ≥90%',
                        'target' => 'Penyelesaian aset kreatif tepat deadline ≥90%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Efisiensi Budget Produksi Konten',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Realisasi anggaran produksi ≤100% dari alokasi',
                        'target' => 'Realisasi anggaran produksi ≤100% dari alokasi',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'CONTENT CREATOR PEBY MOTOR (4 CABANG)',
                'aliases' => ['Content Creator Peby Motor', 'Content Creator Peby Motor (4 Cabang)'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Volume Output Konten Cabang',
                        'weight' => 35,
                        'frequency' => 'M',
                        'formula' => 'Jumlah video/foto terunggah vs target mingguan',
                        'target' => '100% kuota konten cabang terunggah',
                        'unit' => 'Jumlah',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Engagement Lokal & Trafik Bengkel',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Interaksi audiens lokal per cabang & lead datang',
                        'target' => 'Mencapai target interaksi audiens & kedatangan unit',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Konsistensi Konten Promo Servis & Sparepart',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Kesesuaian materi promosi berkala cabang',
                        'target' => '100% promo servis & sparepart tayang berkala',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Kualitas & Estetika Visual Aset Bengkel',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Skor evaluasi kesesuaian brand guideline ≥90%',
                        'target' => 'Skor evaluasi kesesuaian brand guideline ≥90%',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Sales & Marketing',
                'position' => 'ADMIN CHAT WA & ADMIN MEDSOS',
                'aliases' => ['Admin Sosial Media', 'Admin Marketplace', 'Admin Chat WA & Admin Medsos'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Response Time Chat / Direct Message',
                        'weight' => 30,
                        'frequency' => 'H',
                        'formula' => 'Waktu tanggap chat ≤5 menit saat jam operasional',
                        'target' => 'Waktu tanggap chat ≤5 menit saat jam operasional',
                        'unit' => 'Menit',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Conversion Rate Chat ke Closing',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Jumlah order via chat ÷ Total prospek chat x 100%',
                        'target' => 'Rasio closing chat mencapai target',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Kepuasan Pelanggan via Chat (CSAT)',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Penilaian kepuasan konsumen chat ≥4.8/5.0',
                        'target' => 'Penilaian kepuasan konsumen chat ≥4.8/5.0',
                        'unit' => 'Skor',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Disiplin Input Database & Tagging Prospek',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Database kontak ter-update 100% harian',
                        'target' => 'Database kontak ter-update 100% harian',
                        'unit' => '%',
                    ],
                ],
            ],

            // ==========================================
            // OPERATIONAL
            // ==========================================
            [
                'dept' => 'Operational',
                'position' => 'OPERATIONS MANAGER',
                'aliases' => ['Operation Manager', 'Manager Operational', 'Operations Manager'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Rasio Biaya Operasional (Opex vs Omzet)',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Total Opex ÷ Total Omzet x 100%',
                        'target' => 'Rasio Opex berada dalam batas efisiensi anggaran',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'SLA Proses Inti Operasional Pusat & Cabang',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Proses selesai sesuai standar waktu ÷ Total x 100%',
                        'target' => '100% proses operasional memenuhi standar SLA',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Zero Insiden Berat Operasional Gudang/Cabang',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Jumlah insiden gangguan berat ≤ 2 per bulan',
                        'target' => 'Jumlah insiden gangguan berat ≤ 2 per bulan',
                        'unit' => 'Insiden',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Utilisasi Kapasitas Gudang & Bengkel Peby Motor',
                        'weight' => 15,
                        'frequency' => 'B',
                        'formula' => 'Output aktual ÷ Kapasitas terpasang x 100%',
                        'target' => 'Utilisasi kapasitas mencapai target optimal',
                        'unit' => '%',
                    ],
                    [
                        'no' => 5,
                        'name' => 'Kepatuhan SOP Lintas Departemen & Cabang',
                        'weight' => 10,
                        'frequency' => 'B',
                        'formula' => 'Skor audit SOP operasional ≥90/100',
                        'target' => 'Skor audit SOP operasional ≥90/100',
                        'unit' => 'Skor',
                    ],
                ],
            ],
            [
                'dept' => 'Operational',
                'position' => 'KEPALA GUDANG JANGEBY',
                'aliases' => ['Kepala Gudang', 'Wakil Kepala Gudang', 'Kepala Gudang Jangeby'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Akurasi Stok Gudang (Inventory Record Accuracy)',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Stok fisik cocok sistem ÷ Total SKU dihitung x 100%',
                        'target' => 'Akurasi stok fisik vs sistem ≥99.5%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Fulfillment Order Tepat Waktu (SLA Outbound)',
                        'weight' => 25,
                        'frequency' => 'H',
                        'formula' => 'Pesanan siap kirim tepat jadwal ÷ Total order x 100%',
                        'target' => '100% order outbound siap tepat jadwal',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Tingkat Kerusakan Barang Gudang (Shrinkage/Damage)',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Nilai barang rusak/hilang ÷ Total nilai stok ≤0.2%',
                        'target' => 'Nilai barang rusak/hilang ÷ Total nilai stok ≤0.2%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Efisiensi Penerimaan & Bongkar Muat (Inbound SLA)',
                        'weight' => 15,
                        'frequency' => 'B',
                        'formula' => 'Waktu penerimaan hingga rak penyimpanan ≤4 jam',
                        'target' => 'Waktu penerimaan hingga rak penyimpanan ≤4 jam',
                        'unit' => 'Jam',
                    ],
                    [
                        'no' => 5,
                        'name' => 'Kerapihan & Keselamatan Kerja Gudang (5R & K3)',
                        'weight' => 10,
                        'frequency' => 'M',
                        'formula' => 'Skor inspeksi 5R dan kepatuhan K3 ≥85/100',
                        'target' => 'Skor inspeksi 5R dan kepatuhan K3 ≥85/100',
                        'unit' => 'Skor',
                    ],
                ],
            ],
            [
                'dept' => 'Operational',
                'position' => 'PIC GUDANG & TIM PRODUKSI JANGEBY',
                'aliases' => ['PIC Gudang', 'Staf Gudang', 'PIC Gudang & Tim Produksi Jangeby'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Kecepatan & Akurasi Packing/Picking',
                        'weight' => 35,
                        'frequency' => 'H',
                        'formula' => 'Jumlah koli/pesanan selesai per jam kerja sesuai SOP',
                        'target' => '100% pemenuhan kuota koli per jam kerja',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Zero Kesalahan Kirim Barang (Picking Accuracy)',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Pesanan tanpa komplain salah kirim ≥99.5%',
                        'target' => 'Pesanan tanpa komplain salah kirim ≥99.5%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Disiplin Input Mutasi Barang Fisik ke Sistem',
                        'weight' => 20,
                        'frequency' => 'H',
                        'formula' => 'Semua pergerakan barang dicatat langsung hari itu',
                        'target' => 'Pergerakan barang ter-input 100% pada hari H',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Kebersihan & Penataan Rak Kerja Harian',
                        'weight' => 15,
                        'frequency' => 'H',
                        'formula' => 'Checklist kebersihan area kerja tuntas tiap akhir shift',
                        'target' => 'Checklist tuntas 100% tiap akhir shift',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Operational',
                'position' => 'KEPALA GUDANG ONLINE CABANG',
                'aliases' => ['Kepala Gudang Online Cabang'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'SLA Pengiriman Pesanan Marketplace',
                        'weight' => 30,
                        'frequency' => 'H',
                        'formula' => 'Pesanan diproses & diserahkan kurir sebelum cutoff',
                        'target' => '100% pesanan diproses sebelum cutoff',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Akurasi Sinkronisasi Stok Sistem vs Fisik',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Selisih stok marketplace vs fisik = 0 item',
                        'target' => 'Selisih stok marketplace vs fisik = 0 item',
                        'unit' => 'Item',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Pengelolaan Retur Barang Pembeli',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Retur diverifikasi & masuk sistem ≤24 jam kedatangan',
                        'target' => 'Retur diverifikasi & masuk sistem ≤24 jam kedatangan',
                        'unit' => 'Jam',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Efisiensi Pemakaian Bahan Kemasan (Bubble/Kardus)',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Biaya packing per paket sesuai standar biaya baku',
                        'target' => 'Biaya packing sesuai standar baku',
                        'unit' => 'Rp',
                    ],
                ],
            ],
            [
                'dept' => 'Operational',
                'position' => 'PIC BAGIAN GUDANG ONLINE (INBOUND/OUTBOUND/RETURN/LOGISTIK)',
                'aliases' => ['Picker', 'Bagging', 'Packing Staff', 'Checker', 'Inputer', 'PIC Bagian Gudang Online (Inbound/Outbound/Return/Logistik)'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Kecepatan Penyiapan Paket Online',
                        'weight' => 35,
                        'frequency' => 'H',
                        'formula' => 'Rata-rata menit per paket selesai dikemas',
                        'target' => 'Rata-rata menit per paket sesuai standar SLA',
                        'unit' => 'Menit',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Akurasi Labeling & Scanning Resi',
                        'weight' => 30,
                        'frequency' => 'H',
                        'formula' => 'Persentase resi terpindai & tertempel tepat target 100%',
                        'target' => 'Persentase resi terpindai & tertempel 100%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Penyortiran Barang Retur & Update Status',
                        'weight' => 20,
                        'frequency' => 'H',
                        'formula' => 'Barang retur selesai diinspeksi di hari penerimaan',
                        'target' => 'Barang retur selesai diinspeksi di hari penerimaan',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Kehadiran & Disiplin Kerja Shift',
                        'weight' => 15,
                        'frequency' => 'B',
                        'formula' => 'Persentase ketepatan kehadiran kerja ≥95%',
                        'target' => 'Persentase ketepatan kehadiran kerja ≥95%',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Operational',
                'position' => 'KEPALA MEKANIK & TIM MEKANIK PEBY MOTOR (4 CABANG)',
                'aliases' => ['Kepala Mekanik', 'Mekanik', 'Kepala Mekanik & Tim Mekanik Peby Motor (4 Cabang)'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Pencapaian Target Unit Servis Masuk',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Jumlah unit motor diservis ÷ Target unit x 100%',
                        'target' => '100% target unit motor diservis tercapai',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Zero Re-work / Komplain Kualitas Servis',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Persentase motor kembali komplain perbaikan ≤2%',
                        'target' => 'Persentase motor kembali komplain perbaikan ≤2%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'SLA Durasi Pengerjaan Servis Rutin',
                        'weight' => 20,
                        'frequency' => 'H',
                        'formula' => 'Pengerjaan servis berkala selesai ≤45 menit',
                        'target' => 'Pengerjaan servis berkala selesai ≤45 menit',
                        'unit' => 'Menit',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Perawatan Toolkit & Kebersihan Stall Bengkel',
                        'weight' => 20,
                        'frequency' => 'H',
                        'formula' => 'Checklist kelengkapan alat kerja & kebersihan 100%',
                        'target' => 'Checklist kelengkapan alat kerja & kebersihan 100%',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'Operational',
                'position' => 'KEPALA TOKO & PRAMUNIAGA PEBY MOTOR (4 CABANG / 4 KEPALA TOKO & 8 PRAMUNIAGA)',
                'aliases' => ['Kepala Toko', 'Pramuniaga', 'Kepala Toko & Pramuniaga Peby Motor'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Pencapaian Target Omzet Toko & Part',
                        'weight' => 35,
                        'frequency' => 'B',
                        'formula' => 'Omzet toko per cabang ÷ Target omzet x 100%',
                        'target' => '100% target omzet toko per cabang tercapai',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Kerapian Display & Ketersediaan Fast Moving Part',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Ketersediaan stok fast moving ≥98% di rak etalase',
                        'target' => 'Ketersediaan stok fast moving ≥98% di rak etalase',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Keramahan & Skor Kepuasan Konsumen Toko',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Skor rating kepuasan pelanggan toko ≥4.7/5.0',
                        'target' => 'Skor rating kepuasan pelanggan toko ≥4.7/5.0',
                        'unit' => 'Skor',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Akurasi Pembukuan Kas Harian Toko',
                        'weight' => 20,
                        'frequency' => 'H',
                        'formula' => 'Selisih kas harian penjualan toko = Rp 0',
                        'target' => 'Selisih kas harian penjualan toko = Rp 0',
                        'unit' => 'Rp',
                    ],
                ],
            ],

            // ==========================================
            // FINANCE, ACCOUNTING & TAX (FAT)
            // ==========================================
            [
                'dept' => 'FAT',
                'position' => 'FINANCE & ACCOUNTING MANAGER',
                'aliases' => ['FAT Manager', 'Manager FAT', 'Finance & Accounting Manager'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Ketepatan & Ketersediaan Laporan Keuangan (L/R, Neraca, Arus Kas)',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Laporan terbit ≤ tanggal 7 bulan berikutnya',
                        'target' => 'Laporan terbit ≤ tanggal 7 bulan berikutnya',
                        'unit' => 'Tanggal',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Kesehatan Arus Kas & Proyeksi Likuiditas',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Saldo kas operasional mencukupi ≥1.5x Opex bulanan',
                        'target' => 'Saldo kas operasional mencukupi ≥1.5x Opex bulanan',
                        'unit' => 'Rasio',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Kepatuhan Pajak (Lapor & Setor Tepat Waktu)',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'SPT Masa/Tahunan lapor & setor 100% tanpa denda',
                        'target' => 'SPT Masa/Tahunan lapor & setor 100% tanpa denda',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Efisiensi Biaya & Kepatuhan Anggaran (Budget)',
                        'weight' => 15,
                        'frequency' => 'B',
                        'formula' => 'Deviasi realisasi vs budget pusat & cabang ≤±5%',
                        'target' => 'Deviasi realisasi vs budget pusat & cabang ≤±5%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 5,
                        'name' => 'Hasil Audit & Rekonsiliasi Tanpa Temuan Mayor',
                        'weight' => 10,
                        'frequency' => 'K',
                        'formula' => 'Temuan audit eksternal/internal minor ≤3 mayor 0',
                        'target' => 'Temuan audit eksternal/internal minor ≤3 mayor 0',
                        'unit' => 'Temuan',
                    ],
                ],
            ],
            [
                'dept' => 'FAT',
                'position' => 'SUPERVISOR FAT',
                'aliases' => ['Supervisor FAT', 'FAT Supervisor'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Kecepatan Rekonsiliasi Bank & Akun Kas',
                        'weight' => 30,
                        'frequency' => 'M',
                        'formula' => 'Rekonsiliasi seluruh rekening selesai ≤ tanggal 3',
                        'target' => 'Rekonsiliasi seluruh rekening selesai ≤ tanggal 3',
                        'unit' => 'Tanggal',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Pengendalian Piutang Usaha (AR Aging)',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Piutang melewati jatuh tempo (overdue) ≤5%',
                        'target' => 'Piutang melewati jatuh tempo (overdue) ≤5%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Verifikasi Dokumen Tagihan Supplier (AP)',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Invoice diverifikasi & dijadwalkan bayar tepat waktu',
                        'target' => '100% invoice diverifikasi tepat waktu',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Review Laporan Kasir Toko & Cabang',
                        'weight' => 20,
                        'frequency' => 'H',
                        'formula' => 'Laporan penerimaan cabang divalidasi harian tanpa selisih',
                        'target' => 'Laporan penerimaan cabang divalidasi harian tanpa selisih',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'FAT',
                'position' => 'ACCOUNTING STAFF',
                'aliases' => ['Accounting Staff', 'Staff Data Entry'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Ketepatan & Kecepatan Jurnal Transaksi Harian',
                        'weight' => 35,
                        'frequency' => 'H',
                        'formula' => 'Semua bukti transaksi ter-input sistem ≤1x24 jam',
                        'target' => 'Semua bukti transaksi ter-input sistem ≤1x24 jam',
                        'unit' => 'Jam',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Penyusunan Kertas Kerja Laporan Keuangan',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Draft laporan keuangan siap ≤ tanggal 5 tiap bulan',
                        'target' => 'Draft laporan keuangan siap ≤ tanggal 5 tiap bulan',
                        'unit' => 'Tanggal',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Pengelolaan Faktur Pajak & e-Faktur',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Faktur pajak keluaran diterbitkan tepat waktu tanpa revisi',
                        'target' => '100% faktur pajak keluaran tepat waktu tanpa revisi',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Kelengkapan & Kerapihan Arsip Bukti Kas/Bank',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Dokumen bukti bayar/terima terarsip rapi & lengkap',
                        'target' => '100% dokumen bukti terarsip rapi & lengkap',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'FAT',
                'position' => 'FINANCE STAFF',
                'aliases' => ['Finance Staff', 'Admin FAT'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Ketepatan Eksekusi Pembayaran Tagihan/Vendor',
                        'weight' => 35,
                        'frequency' => 'M',
                        'formula' => 'Jadwal payment run tepat waktu tanpa komplain vendor',
                        'target' => '100% pembayaran tepat waktu tanpa komplain vendor',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Monitoring & Penagihan Piutang (AR Collection)',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Penagihan jatuh tempo tertagih ≥90% sesuai termin',
                        'target' => 'Penagihan jatuh tempo tertagih ≥90% sesuai termin',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Pengelolaan Petty Cash & Reimbursement',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Klaim operasional diverifikasi & cair sesuai jadwal',
                        'target' => '100% klaim diverifikasi & cair sesuai jadwal',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Pencocokan Mutasi Bank Harian',
                        'weight' => 15,
                        'frequency' => 'H',
                        'formula' => 'Mutasi rekening bank dicocokkan tiap pagi hari',
                        'target' => '100% mutasi bank dicocokkan tiap pagi hari',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'FAT',
                'position' => 'KASIR PUSAT & KASIR PEBY MOTOR (CABANG)',
                'aliases' => ['Kasir', 'Kasir Pusat & Kasir Peby Motor (Cabang)'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Zero Selisih Fisik Kas vs Sistem POS/Register',
                        'weight' => 35,
                        'frequency' => 'H',
                        'formula' => 'Selisih kas fisik vs laporan sistem kasir = Rp 0',
                        'target' => 'Selisih kas fisik vs laporan sistem kasir = Rp 0',
                        'unit' => 'Rp',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Ketepatan Setoran Bank / Transfer Penjualan',
                        'weight' => 25,
                        'frequency' => 'H',
                        'formula' => 'Uang penjualan tunai disetor ke bank tepat waktu',
                        'target' => '100% uang penjualan disetor tepat waktu',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Ketertiban Penerimaan Pembayaran Non-Tunai (QRIS/EDC)',
                        'weight' => 20,
                        'frequency' => 'H',
                        'formula' => 'Bukti transfer/settlement EDC diarsipkan lengkap',
                        'target' => '100% bukti settlement/transfer terarsip lengkap',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Penyusunan Bukti Transaksi & Nota Penjualan',
                        'weight' => 20,
                        'frequency' => 'H',
                        'formula' => 'Nota dan bukti transaksi tersusun rapi per shift',
                        'target' => '100% nota dan bukti tersusun rapi per shift',
                        'unit' => '%',
                    ],
                ],
            ],

            // ==========================================
            // HR & GENERAL AFFAIRS
            // ==========================================
            [
                'dept' => 'HR & GA',
                'position' => 'HR & GA MANAGER',
                'aliases' => ['HR & GA Manager'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Kecepatan Pemenuhan Posisi (Time-to-Fill)',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Staf ≤30 hari Supervisor/Manager ≤45 hari',
                        'target' => 'Staf ≤30 hari, Supervisor/Manager ≤45 hari',
                        'unit' => 'Hari',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Retensi Karyawan & Pengendalian Turnover',
                        'weight' => 25,
                        'frequency' => 'K',
                        'formula' => 'Turnover sukarela karyawan Jangeby Grup ≤15%/tahun',
                        'target' => 'Turnover sukarela karyawan Jangeby Grup ≤15%/tahun',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Kepatuhan Hukum Ketenagakerjaan & BPJS',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Regulasi BPJS Ketenagakerjaan & Kontrak Kerja 100% patuh',
                        'target' => '100% patuh regulasi BPJS & kontrak kerja',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Penyelesaian Isu / Hubungan Industrial',
                        'weight' => 15,
                        'frequency' => 'B',
                        'formula' => 'Kasus karyawan selesai ≤14 hari ≥90%',
                        'target' => 'Kasus karyawan selesai ≤14 hari ≥90%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 5,
                        'name' => 'Pengelolaan Budget GA & Fasilitas Kerja',
                        'weight' => 15,
                        'frequency' => 'B',
                        'formula' => 'Realisasi budget GA ≤100% dengan efisiensi 5%',
                        'target' => 'Realisasi budget GA ≤100% dengan efisiensi 5%',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'HR & GA',
                'position' => 'HR & GA SUPERVISOR',
                'aliases' => ['HR & GA Supervisor'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Ketepatan Waktu & Akurasi Penggajian (Payroll)',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Payroll diproses & ditransfer tepat tgl gajian 100% akurat',
                        'target' => 'Payroll 100% akurat ditransfer tepat tanggal gajian',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Pengawasan Disiplin & Absensi Karyawan',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Tingkat kehadiran tim ≥95% & keterlambatan ≤3%',
                        'target' => 'Tingkat kehadiran tim ≥95% & keterlambatan ≤3%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Monitoring Perpanjangan Kontrak & Legalitas',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Evaluasi kontrak karyawan selesai ≤30 hari sebelum habis',
                        'target' => 'Evaluasi kontrak selesai ≤30 hari sebelum habis',
                        'unit' => 'Hari',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Pemeliharaan Fasilitas & Perizinan Operasional',
                        'weight' => 20,
                        'frequency' => 'B',
                        'formula' => 'Perizinan cabang valid & fasilitas kerja terpelihara baik',
                        'target' => '100% perizinan cabang valid & fasilitas terpelihara baik',
                        'unit' => '%',
                    ],
                ],
            ],
            [
                'dept' => 'HR & GA',
                'position' => 'HR & GA STAFF',
                'aliases' => ['GA Staff', 'HR & GA Staff', 'Staff HR'],
                'indicators' => [
                    [
                        'no' => 1,
                        'name' => 'Administrasi Data Karyawan & Arsip Berkas',
                        'weight' => 30,
                        'frequency' => 'B',
                        'formula' => 'Data master karyawan di HRIS/sistem ter-update 100%',
                        'target' => 'Data master karyawan di HRIS/sistem ter-update 100%',
                        'unit' => '%',
                    ],
                    [
                        'no' => 2,
                        'name' => 'Pengurusan Pendaftaran & Klaim BPJS',
                        'weight' => 25,
                        'frequency' => 'B',
                        'formula' => 'Pendaftaran karyawan baru BPJS selesai ≤7 hari kerja',
                        'target' => 'Pendaftaran karyawan baru BPJS selesai ≤7 hari kerja',
                        'unit' => 'Hari',
                    ],
                    [
                        'no' => 3,
                        'name' => 'Pelaksanaan Logistik Kebutuhan Kantor/ATK',
                        'weight' => 25,
                        'frequency' => 'M',
                        'formula' => 'Ketersediaan ATK & sarana kerja terpenuhi tanpa kendala',
                        'target' => '100% ketersediaan ATK & sarana kerja terpenuhi',
                        'unit' => '%',
                    ],
                    [
                        'no' => 4,
                        'name' => 'Rekapitulasi Jam Kerja Lembur & Izin/Cuti',
                        'weight' => 20,
                        'frequency' => 'M',
                        'formula' => 'Laporan absensi dan cuti ditutup tepat waktu tiap periode',
                        'target' => '100% laporan absensi & cuti ditutup tepat waktu',
                        'unit' => '%',
                    ],
                ],
            ],
        ];

        $codeCounter = 1;

        foreach ($data as $role) {
            $deptName = $role['dept'];
            $posName = $role['position'];
            $aliases = $role['aliases'] ?? [$posName];

            // 1. Resolve Department
            $department = Department::where('name', $deptName)->first();
            if (!$department) {
                // Check if there is an existing department with similar name
                $department = Department::where('name', 'like', "%{$deptName}%")->first();
            }
            if (!$department) {
                $department = Department::create([
                    'name' => $deptName,
                    'is_active' => true,
                ]);
            }

            // 2. Resolve Positions (primary and aliases)
            $matchedPositions = Position::whereIn('name', array_merge([$posName], $aliases))->get();
            if ($matchedPositions->isEmpty()) {
                $newPos = Position::create([
                    'name' => $posName,
                    'department_id' => $department->id,
                    'is_active' => true,
                ]);
                $matchedPositions = collect([$newPos]);
            }

            // 3. Process Indicators and create KPI Template for each matched position
            $createdIndicators = [];
            foreach ($role['indicators'] as $indData) {
                $indCode = sprintf('KPI-%03d', $codeCounter++);
                
                // Cari apakah indikator dengan nama sama sudah ada
                $indicator = KpiIndicator::where('name', $indData['name'])->first();
                if (!$indicator) {
                    $indicator = KpiIndicator::create([
                        'name' => $indData['name'],
                        'code' => $indCode,
                        'category' => $deptName,
                        'type' => 'individual',
                        'measurement_unit' => $indData['unit'],
                        'frequency' => $indData['frequency'],
                        'calculation_method' => $indData['formula'],
                        'description' => "Rumus: {$indData['formula']} | Target: {$indData['target']}",
                        'rating_scale_id' => $ratingScale->id,
                        'is_active' => true,
                    ]);
                } else {
                    $indicator->update([
                        'category' => $deptName,
                        'frequency' => $indData['frequency'],
                        'calculation_method' => $indData['formula'],
                        'measurement_unit' => $indData['unit'],
                    ]);
                }

                $createdIndicators[] = [
                    'indicator' => $indicator,
                    'weight' => $indData['weight'],
                    'target' => $indData['target'],
                    'frequency' => $indData['frequency'],
                    'calculation_method' => $indData['formula'],
                    'sort_order' => $indData['no'],
                ];
            }

            // 4. Create/Update Template for matched positions
            foreach ($matchedPositions as $position) {
                $templateName = "KPI - " . $position->name;
                $template = KpiTemplate::where('position_id', $position->id)->first();
                if (!$template) {
                    $template = KpiTemplate::create([
                        'name' => $templateName,
                        'description' => "Template standar KPI untuk jabatan {$position->name} ({$deptName})",
                        'scope' => 'position',
                        'position_id' => $position->id,
                        'rating_scale_id' => $ratingScale->id,
                        'is_active' => true,
                    ]);
                } else {
                    $template->update([
                        'name' => $templateName,
                        'rating_scale_id' => $ratingScale->id,
                        'is_active' => true,
                    ]);
                }

                // Sync Template Indicators
                $template->templateIndicators()->delete();
                foreach ($createdIndicators as $item) {
                    $template->templateIndicators()->create([
                        'indicator_id' => $item['indicator']->id,
                        'weight' => $item['weight'],
                        'target' => $item['target'],
                        'frequency' => $item['frequency'],
                        'calculation_method' => $item['calculation_method'],
                        'scoring_points' => $item['indicator']->scoring_points,
                        'sort_order' => $item['sort_order'],
                    ]);
                }
            }
        }
    }
}

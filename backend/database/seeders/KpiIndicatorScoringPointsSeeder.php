<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KpiIndicator;
use App\Models\KpiTemplateIndicator;

class KpiIndicatorScoringPointsSeeder extends Seeder
{
    public function run(): void
    {
        $indicators = KpiIndicator::all();

        foreach ($indicators as $ind) {
            $points = $this->generateScoringPoints($ind);
            $ind->update(['scoring_points' => $points]);

            // Sync to template indicators
            KpiTemplateIndicator::where('indicator_id', $ind->id)
                ->update(['scoring_points' => $points]);
        }

        $this->command->info("Updated scoring points for {$indicators->count()} KPI indicators and their template indicators.");
    }

    private function generateScoringPoints(KpiIndicator $ind): array
    {
        $name = strtolower($ind->name);
        $formula = strtolower($ind->calculation_method ?? '');
        $target = strtolower($ind->target ?? '');

        // 1. Zero Selisih Kas / Rekonsiliasi Kasir
        if (str_contains($name, 'selisih') || str_contains($name, 'kas fisik') || str_contains($name, 'pembukuan kas')) {
            return [
                ['point' => 5, 'grade' => 'A', 'score' => 100, 'label' => 'Sangat Baik', 'criteria' => 'Selisih kas fisik vs sistem = Rp 0 (100% seimbang) dan pembukuan tuntas tepat shift.'],
                ['point' => 4, 'grade' => 'B', 'score' => 85, 'label' => 'Baik', 'criteria' => 'Selisih Rp 0 di akhir hari setelah rekonsiliasi mandiri tanpa kendala operasional.'],
                ['point' => 3, 'grade' => 'C', 'score' => 70, 'label' => 'Cukup', 'criteria' => 'Terjadi selisih minor (< Rp 10.000) dan berhasil ditelusuri serta diselesaikan hari yang sama.'],
                ['point' => 2, 'grade' => 'D', 'score' => 60, 'label' => 'Kurang', 'criteria' => 'Terjadi selisih Rp 10.000 - Rp 50.000 atau keterlambatan setor bukti transaksi.'],
                ['point' => 1, 'grade' => 'E', 'score' => 40, 'label' => 'Sangat Kurang', 'criteria' => 'Selisih > Rp 50.000 atau selisih berulang yang tidak dapat dipertanggungjawabkan.'],
            ];
        }

        // 2. Zero Insiden / K3 / Re-work / Kerusakan (Damage/Shrinkage)
        if (str_contains($name, 'insiden') || str_contains($name, 'kerusakan') || str_contains($name, 're-work') || str_contains($name, 'kesalahan kirim')) {
            return [
                ['point' => 5, 'grade' => 'A', 'score' => 100, 'label' => 'Sangat Baik', 'criteria' => 'Zero insiden / kesalahan (0 kasus), tingkat kerusakan 0%, SOP K3 dipatuhi 100%.'],
                ['point' => 4, 'grade' => 'B', 'score' => 85, 'label' => 'Baik', 'criteria' => 'Maksimal 1 temuan minor yang tertangani seketika tanpa menimbulkan kerugian.'],
                ['point' => 3, 'grade' => 'C', 'score' => 70, 'label' => 'Cukup', 'criteria' => 'Terjadi 1-2 kesalahan kecil/minor namun segera diselesaikan dengan komitmen perbaikan.'],
                ['point' => 2, 'grade' => 'D', 'score' => 60, 'label' => 'Kurang', 'criteria' => 'Terjadi kelalaian atau tingkat kerusakan melebihi toleransi standar (perlu peringatan).'],
                ['point' => 1, 'grade' => 'E', 'score' => 40, 'label' => 'Sangat Kurang', 'criteria' => 'Terjadi insiden berat, barang hilang/rusak signifikan, atau komplain berulang.'],
            ];
        }

        // 3. Omzet / GMV / Leads / Closing / Kontrak / ROAS / Revenue
        if (str_contains($name, 'omzet') || str_contains($name, 'gmv') || str_contains($name, 'closing') || str_contains($name, 'roas') || str_contains($name, 'leads')) {
            return [
                ['point' => 5, 'grade' => 'A', 'score' => 100, 'label' => 'Sangat Baik', 'criteria' => 'Capaian realisasi ≥ 100% dari target periode yang ditetapkan.'],
                ['point' => 4, 'grade' => 'B', 'score' => 85, 'label' => 'Baik', 'criteria' => 'Capaian realisasi 90% - 99.9% dari target periode (kinerja solid).'],
                ['point' => 3, 'grade' => 'C', 'score' => 70, 'label' => 'Cukup', 'criteria' => 'Capaian realisasi 80% - 89.9% dari target periode (mendekati target standar).'],
                ['point' => 2, 'grade' => 'D', 'score' => 60, 'label' => 'Kurang', 'criteria' => 'Capaian realisasi 70% - 79.9% dari target periode (perlu strategi peningkatan).'],
                ['point' => 1, 'grade' => 'E', 'score' => 40, 'label' => 'Sangat Kurang', 'criteria' => 'Capaian realisasi < 70% dari target periode (di bawah standar minimal).'],
            ];
        }

        // 4. Response Time / SLA Waktu Pengerjaan / Kecepatan / Time-to-Fill
        if (str_contains($name, 'response time') || str_contains($name, 'sla') || str_contains($name, 'kecepatan') || str_contains($name, 'time-to-fill') || str_contains($name, 'durasi')) {
            return [
                ['point' => 5, 'grade' => 'A', 'score' => 100, 'label' => 'Sangat Baik', 'criteria' => 'Konsisten melampaui standar kecepatan (selesai lebih cepat dari batas waktu SLA).'],
                ['point' => 4, 'grade' => 'B', 'score' => 85, 'label' => 'Baik', 'criteria' => '100% pekerjaan selesai tepat waktu sesuai batas durasi/SLA yang ditentukan.'],
                ['point' => 3, 'grade' => 'C', 'score' => 70, 'label' => 'Cukup', 'criteria' => '80% - 94% pekerjaan tepat SLA, keterlambatan minor segera diatasi.'],
                ['point' => 2, 'grade' => 'D', 'score' => 60, 'label' => 'Kurang', 'criteria' => '65% - 79% tepat waktu, kerap terjadi penumpukan atau penundaan antrean.'],
                ['point' => 1, 'grade' => 'E', 'score' => 40, 'label' => 'Sangat Kurang', 'criteria' => 'Sering melewati batas waktu (>35% keterlambatan) dan memicu komplain.'],
            ];
        }

        // 5. Akurasi Stok / Picking / Scanning / Labeling / Jurnal Transaksi
        if (str_contains($name, 'akurasi') || str_contains($name, 'jurnal') || str_contains($name, 'sinkronisasi') || str_contains($name, 'labeling')) {
            return [
                ['point' => 5, 'grade' => 'A', 'score' => 100, 'label' => 'Sangat Baik', 'criteria' => 'Akurasi 99% - 100%, pencatatan mutasi langsung di hari H tanpa selisih data.'],
                ['point' => 4, 'grade' => 'B', 'score' => 85, 'label' => 'Baik', 'criteria' => 'Akurasi 95% - 98.9%, kekeliruan minor segera dikoreksi sebelum verifikasi akhir.'],
                ['point' => 3, 'grade' => 'C', 'score' => 70, 'label' => 'Cukup', 'criteria' => 'Akurasi 90% - 94.9%, terdapat 1-2 koreksi catatan namun tidak berdampak fatal.'],
                ['point' => 2, 'grade' => 'D', 'score' => 60, 'label' => 'Kurang', 'criteria' => 'Akurasi 80% - 89.9%, sering terjadi kelalaian pencatatan atau keterlambatan input.'],
                ['point' => 1, 'grade' => 'E', 'score' => 40, 'label' => 'Sangat Kurang', 'criteria' => 'Akurasi < 80%, ketidaksesuaian data berulang dan menimbulkan masalah operasional.'],
            ];
        }

        // 6. Rating / Kepuasan Konsumen (CSAT) / Engagement
        if (str_contains($name, 'kepuasan') || str_contains($name, 'csat') || str_contains($name, 'engagement') || str_contains($name, 'sentimen')) {
            return [
                ['point' => 5, 'grade' => 'A', 'score' => 100, 'label' => 'Sangat Baik', 'criteria' => 'Skor rating/kepuasan ≥ 4.8/5.0 atau sentimen positif ≥ 90% (pelayanan prima).'],
                ['point' => 4, 'grade' => 'B', 'score' => 85, 'label' => 'Baik', 'criteria' => 'Skor rating/kepuasan 4.5 - 4.7/5.0 atau sentimen positif 80% - 89%.'],
                ['point' => 3, 'grade' => 'C', 'score' => 70, 'label' => 'Cukup', 'criteria' => 'Skor rating/kepuasan 4.0 - 4.4/5.0 atau sentimen positif 70% - 79%.'],
                ['point' => 2, 'grade' => 'D', 'score' => 60, 'label' => 'Kurang', 'criteria' => 'Skor rating/kepuasan 3.5 - 3.9/5.0, beberapa komplain belum tertangani baik.'],
                ['point' => 1, 'grade' => 'E', 'score' => 40, 'label' => 'Sangat Kurang', 'criteria' => 'Skor rating/kepuasan < 3.5/5.0, banyak keluhan tidak puas dari konsumen.'],
            ];
        }

        // 7. Output Konten / Video / Proposal / Disiplin / Event
        if (str_contains($name, 'konten') || str_contains($name, 'proposal') || str_contains($name, 'event') || str_contains($name, 'laporan') || str_contains($name, 'payroll')) {
            return [
                ['point' => 5, 'grade' => 'A', 'score' => 100, 'label' => 'Sangat Baik', 'criteria' => 'Target volume output 100% tercapai tepat waktu dengan kualitas visual/data di atas standar.'],
                ['point' => 4, 'grade' => 'B', 'score' => 85, 'label' => 'Baik', 'criteria' => 'Output terealisasi 90% - 99% sesuai jadwal dan rencana yang ditentukan.'],
                ['point' => 3, 'grade' => 'C', 'score' => 70, 'label' => 'Cukup', 'criteria' => 'Output terealisasi 80% - 89%, ada keterlambatan minor tanpa dampak signifikan.'],
                ['point' => 2, 'grade' => 'D', 'score' => 60, 'label' => 'Kurang', 'criteria' => 'Output terealisasi 65% - 79%, beberapa jadwal atau dokumen tertunda.'],
                ['point' => 1, 'grade' => 'E', 'score' => 40, 'label' => 'Sangat Kurang', 'criteria' => 'Output terealisasi < 65% dari target rencana, produktivitas di bawah standar.'],
            ];
        }

        // 8. Kepatuhan SOP / 5R / Kebersihan / Kehadiran / K3 / Audit
        return [
            ['point' => 5, 'grade' => 'A', 'score' => 100, 'label' => 'Sangat Baik', 'criteria' => 'Kepatuhan SOP/5R/disiplin ≥ 95%, nihil catatan audit, implementasi konsisten.'],
            ['point' => 4, 'grade' => 'B', 'score' => 85, 'label' => 'Baik', 'criteria' => 'Kepatuhan 85% - 94%, hanya terdapat catatan evaluasi administratif minor.'],
            ['point' => 3, 'grade' => 'C', 'score' => 70, 'label' => 'Cukup', 'criteria' => 'Kepatuhan 75% - 84%, ada beberapa poin inspeksi yang memerlukan perapihan.'],
            ['point' => 2, 'grade' => 'D', 'score' => 60, 'label' => 'Kurang', 'criteria' => 'Kepatuhan 60% - 74%, terjadi ketidakdisiplinan atau pengabaian checklist harian.'],
            ['point' => 1, 'grade' => 'E', 'score' => 40, 'label' => 'Sangat Kurang', 'criteria' => 'Kepatuhan < 60%, temuan audit mayor atau pelanggaran SOP berulang.'],
        ];
    }
}

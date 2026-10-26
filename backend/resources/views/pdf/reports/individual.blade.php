<!DOCTYPE html>
<html>
<head>
    <title>Laporan Individual Karyawan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; margin: 15px; line-height: 1.3; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
        .header h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 3px 0 0; font-size: 11px; color: #555; }

        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta-table td { padding: 4px 6px; border: 1px solid #ddd; }
        .meta-label { width: 22%; font-weight: bold; background-color: #f8f9fa; }

        .section-title { font-size: 12px; font-weight: bold; background-color: #e9ecef; padding: 4px 8px; margin: 10px 0 5px 0; border: 1px solid #ccc; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 4px 6px; font-size: 10px; }
        table.data-table th { background-color: #f1f3f5; font-weight: bold; text-align: center; }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p><b>LAPORAN RINGKASAN INDIVIDUAL KARYAWAN (360° SUMMARY)</b></p>
        <p>Dicetak Pada: {{ date('d F Y') }}</p>
    </div>

    @php
        $emp = $data->employee ?? null;
        $assignment = $emp?->primaryAssignment ?? $emp?->assignments->first();
        $attSummary = $data->attendance_summary ?? [];
    @endphp

    <!-- Metadata Karyawan -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Nama Karyawan</td>
            <td><b>{{ $emp->name ?? '-' }}</b></td>
            <td class="meta-label">NIP / Code</td>
            <td>{{ $emp->nip ?? $emp->employee_id ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Jabatan</td>
            <td>{{ $assignment->position->name ?? '-' }}</td>
            <td class="meta-label">Departemen</td>
            <td>{{ $assignment->department->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tanggal Masuk</td>
            <td>{{ $emp->join_date ? \Carbon\Carbon::parse($emp->join_date)->format('d/m/Y') : '-' }}</td>
            <td class="meta-label">Status Kerja</td>
            <td>{{ strtoupper($emp->employment_status ?? 'Tetap') }}</td>
        </tr>
    </table>

    <!-- Ringkasan Kehadiran -->
    <div class="section-title">1. REKAPITULASI KEHADIRAN (PERIODE TERAKHIR)</div>
    <table class="data-table text-center">
        <thead>
            <tr>
                <th>Hadir</th>
                <th>Terlambat (Frekuensi)</th>
                <th>Total Menit Telat</th>
                <th>Cuti / Izin</th>
                <th>Sakit</th>
                <th>Mangkir / Alpa</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fw-bold text-center">{{ $attSummary['total_present'] ?? 0 }} Hari</td>
                <td class="fw-bold text-center">{{ $attSummary['total_late'] ?? 0 }} Kali</td>
                <td class="fw-bold text-center">{{ $attSummary['total_late_minutes'] ?? 0 }} Menit</td>
                <td class="fw-bold text-center">{{ $attSummary['total_permit'] ?? 0 }} Hari</td>
                <td class="fw-bold text-center">{{ $attSummary['total_sick'] ?? 0 }} Hari</td>
                <td class="fw-bold text-center">{{ $attSummary['total_absent'] ?? 0 }} Hari</td>
            </tr>
        </tbody>
    </table>

    <!-- Riwayat Lembur & Cuti -->
    <div class="section-title">2. RIWAYAT PENGAJUAN (LEMBUR & CUTI)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20%;">Jenis Transaksi</th>
                <th style="width: 25%;">Tanggal</th>
                <th>Keterangan / Alasan</th>
                <th style="width: 15%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data->leaves ?? [] as $l)
                <tr>
                    <td>Pengajuan Cuti</td>
                    <td class="text-center">{{ $l->start_date }} s/d {{ $l->end_date }}</td>
                    <td>{{ $l->reason ?? '-' }}</td>
                    <td class="text-center">{{ strtoupper($l->status ?? 'pending') }}</td>
                </tr>
            @empty
            @endforelse

            @forelse($data->overtimes ?? [] as $o)
                <tr>
                    <td>Pengajuan Lembur ({{ $o->duration_hours ?? 0 }} Jam)</td>
                    <td class="text-center">{{ $o->date }}</td>
                    <td>{{ $o->reason ?? '-' }}</td>
                    <td class="text-center">{{ strtoupper($o->status ?? 'pending') }}</td>
                </tr>
            @empty
            @endforelse

            @if(empty($data->leaves) && empty($data->overtimes))
                <tr><td colspan="4" class="text-center">Belum ada riwayat pengajuan cuti/lembur.</td></tr>
            @endif
        </tbody>
    </table>

    <!-- Riwayat SP & Evaluasi KPI -->
    <div class="section-title">3. PERINGATAN (SP) & EVALUASI KPI</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Kategori</th>
                <th style="width: 20%;">Tanggal / Periode</th>
                <th>Catatan / Skor</th>
                <th style="width: 20%;">Hasil / Level</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data->warnings ?? [] as $w)
                <tr>
                    <td>Surat Peringatan</td>
                    <td class="text-center">{{ $w->warning_date ?? $w->created_at?->format('d/m/Y') }}</td>
                    <td>{{ $w->reason ?? '-' }}</td>
                    <td class="text-center fw-bold">{{ $w->warning_level ?? 'SP' }}</td>
                </tr>
            @empty
            @endforelse

            @forelse($data->kpis ?? [] as $k)
                <tr>
                    <td>Evaluasi KPI</td>
                    <td class="text-center">{{ $k->period->name ?? '-' }}</td>
                    <td>Reviewer Notes: {{ $k->reviewer_notes ?? '-' }}</td>
                    <td class="text-center fw-bold">Skor: {{ number_format($k->final_score ?? 0, 1) }}</td>
                </tr>
            @empty
            @endforelse

            @if(empty($data->warnings) && empty($data->kpis))
                <tr><td colspan="4" class="text-center">Belum ada riwayat SP atau penilaian KPI.</td></tr>
            @endif
        </tbody>
    </table>

</body>
</html>

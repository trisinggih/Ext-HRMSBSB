<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi KPI</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 10px; color: #2d3748; margin: 10px; }
        .header { text-align: center; border-bottom: 2px solid #007A87; padding-bottom: 8px; margin-bottom: 12px; }
        .header h2 { margin: 0; color: #007A87; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 3px 0 0; color: #718096; font-size: 10px; }

        .summary-grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary-grid td { border: 1px solid #cbd5e0; padding: 6px; text-align: center; background-color: #f7fafc; width: 16.6%; }
        .summary-val { font-size: 14px; font-weight: bold; color: #007A87; margin-top: 2px; }
        .summary-lbl { font-size: 9px; color: #718096; text-transform: uppercase; }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th, .data-table td { border: 1px solid #cbd5e0; padding: 5px 6px; font-size: 9px; }
        .data-table th { background-color: #007A87; color: #ffffff; text-align: left; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .badge-approved { background-color: #c6f6d5; color: #22543d; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .badge-review { background-color: #feebc8; color: #744210; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .badge-pending { background-color: #e2e8f0; color: #4a5568; padding: 2px 6px; border-radius: 4px; font-weight: bold; }

        .signatures { width: 100%; margin-top: 20px; text-align: center; }
        .signatures td { width: 33%; vertical-align: bottom; height: 60px; }
        .sig-line { border-bottom: 1px solid #2d3748; width: 75%; margin: 0 auto; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS CORPORATION' }}</h2>
        <p>LAPORAN REKAPITULASI PENILAIAN KPI KARYAWAN</p>
        <p>Periode: <b>{{ $period->name ?? 'Semua Periode' }}</b> | Tanggal Cetak: {{ date('d-m-Y') }}</p>
    </div>

    <!-- Summary Metrics -->
    @if(isset($summary))
    <table class="summary-grid">
        <tr>
            <td>
                <div class="summary-lbl">Total Evaluasi</div>
                <div class="summary-val">{{ $summary['total'] }}</div>
            </td>
            <td>
                <div class="summary-lbl">Disetujui</div>
                <div class="summary-val" style="color: #276749;">{{ $summary['completed'] }}</div>
            </td>
            <td>
                <div class="summary-lbl">Sedang Dinilai</div>
                <div class="summary-val" style="color: #d69e2e;">{{ $summary['in_review'] }}</div>
            </td>
            <td>
                <div class="summary-lbl">Menunggu</div>
                <div class="summary-val" style="color: #718096;">{{ $summary['pending'] }}</div>
            </td>
            <td>
                <div class="summary-lbl">Rata-rata Skor</div>
                <div class="summary-val">{{ $summary['average_score'] !== null ? number_format($summary['average_score'], 2) : '—' }}</div>
            </td>
            <td>
                <div class="summary-lbl">Skor Tertinggi</div>
                <div class="summary-val" style="color: #276749;">{{ $summary['highest_score'] !== null ? number_format($summary['highest_score'], 2) : '—' }}</div>
            </td>
        </tr>
    </table>
    @endif

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="20%">Nama Karyawan</th>
                <th width="10%">NIP</th>
                <th width="15%">Jabatan</th>
                <th width="13%">Divisi</th>
                <th width="15%">Template KPI</th>
                <th width="8%" class="text-center">Status</th>
                <th width="7%" class="text-center">Skor</th>
                <th width="8%" class="text-center">Predikat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assessments as $index => $a)
                @php
                    $score = (float)($a->final_score ?? 0);
                    $predikat = '—';
                    if ($a->final_score !== null) {
                        if ($score >= 4.5) $predikat = 'Sangat Baik';
                        elseif ($score >= 3.5) $predikat = 'Baik';
                        elseif ($score >= 2.5) $predikat = 'Cukup';
                        elseif ($score >= 1.5) $predikat = 'Kurang';
                        else $predikat = 'Sangat Kurang';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><b>{{ $a->employee->name ?? '-' }}</b></td>
                    <td>{{ $a->employee->employee_id ?? '-' }}</td>
                    <td>{{ $a->employee->primaryAssignment->position->name ?? '-' }}</td>
                    <td>{{ $a->employee->primaryAssignment->division->name ?? '-' }}</td>
                    <td>{{ $a->template->name ?? '-' }}</td>
                    <td class="text-center">
                        @if($a->status === 'approved')
                            <span class="badge-approved">Approved</span>
                        @elseif($a->status === 'in_review')
                            <span class="badge-review">In Review</span>
                        @else
                            <span class="badge-pending">Pending</span>
                        @endif
                    </td>
                    <td class="text-center font-size-10">
                        <b>{{ $a->final_score !== null ? number_format($a->final_score, 2) : '—' }}</b>
                    </td>
                    <td class="text-center">
                        {{ $predikat }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data penilaian KPI untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signatures -->
    <table class="signatures">
        <tr>
            <td>
                Dibuat Oleh (HR Staff),<br><br><br><br>
                <div class="sig-line">____________________</div>
                <div style="font-size: 8px; color: #718096; margin-top: 2px;">HR Staff</div>
            </td>
            <td>
                Diverifikasi (HR Manager),<br><br><br><br>
                <div class="sig-line">____________________</div>
                <div style="font-size: 8px; color: #718096; margin-top: 2px;">HR Manager</div>
            </td>
            <td>
                Disetujui (Direksi),<br><br><br><br>
                <div class="sig-line">____________________</div>
                <div style="font-size: 8px; color: #718096; margin-top: 2px;">Director</div>
            </td>
        </tr>
    </table>

</body>
</html>

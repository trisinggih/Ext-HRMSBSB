<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>KPI Assessment - {{ $assessment->employee->name ?? 'Employee' }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 11px; color: #2d3748; margin: 15px; }
        .header { text-align: center; border-bottom: 2px solid #007A87; padding-bottom: 10px; margin-bottom: 15px; }
        .header h2 { margin: 0; color: #007A87; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 3px 0 0; color: #718096; font-size: 11px; }

        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .meta-table td { padding: 5px 8px; vertical-align: top; }
        .meta-label { font-weight: bold; color: #4a5568; width: 22%; background-color: #f7fafc; border: 1px solid #e2e8f0; }
        .meta-value { border: 1px solid #e2e8f0; width: 28%; }

        .score-box { background-color: #ebf8ff; border: 1px solid #bee3f8; border-radius: 6px; padding: 12px; text-align: center; margin-bottom: 15px; }
        .score-num { font-size: 26px; font-weight: bold; color: #2b6cb0; margin: 3px 0; }
        .score-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-weight: bold; font-size: 11px; background-color: #2b6cb0; color: #fff; }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th, .data-table td { border: 1px solid #cbd5e0; padding: 6px 8px; font-size: 10px; }
        .data-table th { background-color: #007A87; color: #ffffff; text-align: left; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .notes-box { border: 1px solid #e2e8f0; background-color: #f7fafc; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .notes-title { font-weight: bold; color: #2d3748; margin-bottom: 4px; }

        .signatures { width: 100%; margin-top: 30px; text-align: center; }
        .signatures td { width: 33%; vertical-align: bottom; height: 70px; }
        .sig-line { border-bottom: 1px solid #2d3748; width: 80%; margin: 0 auto; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS CORPORATION' }}</h2>
        <p>LEMBAR EVALUASI PENILAIAN KINERJA KARYAWAN (KPI)</p>
    </div>

    <!-- Metadata Grid -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Nama Karyawan</td>
            <td class="meta-value"><b>{{ $employee->name ?? '-' }}</b></td>
            <td class="meta-label">Periode Evaluasi</td>
            <td class="meta-value">{{ $assessment->period->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">NIP Karyawan</td>
            <td class="meta-value">{{ $employee->employee_id ?? '-' }}</td>
            <td class="meta-label">Master Template</td>
            <td class="meta-value">{{ $assessment->template->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Jabatan</td>
            <td class="meta-value">{{ $assignment->position->name ?? '-' }}</td>
            <td class="meta-label">Penilai (Evaluator)</td>
            <td class="meta-value">{{ $reviewer->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Divisi / Unit</td>
            <td class="meta-value">{{ $assignment->division->name ?? '-' }}</td>
            <td class="meta-label">Status Penilaian</td>
            <td class="meta-value"><b>{{ strtoupper($assessment->status) }}</b></td>
        </tr>
    </table>

    <!-- Score Summary Box -->
    <div class="score-box">
        <div style="font-size: 11px; color: #4a5568; text-transform: uppercase;">SKOR AKHIR KPI HASIL EVALUASI</div>
        <div class="score-num">
            {{ $assessment->final_score !== null ? number_format($assessment->final_score, 2) : '—' }}
        </div>
        @php
            $score = (float)($assessment->final_score ?? 0);
            $maxScore = collect($ratingLevels)->max('score') ?: 5;
            $predikat = 'Sangat Kurang (E)';
            if ($maxScore > 10) {
                if ($score >= 90) $predikat = 'Sangat Baik (A)';
                elseif ($score >= 80) $predikat = 'Baik (B)';
                elseif ($score >= 70) $predikat = 'Cukup (C)';
                elseif ($score >= 60) $predikat = 'Kurang (D)';
                else $predikat = 'Sangat Kurang (E)';
            } else {
                if ($score >= 4.5) $predikat = 'Sangat Baik (A)';
                elseif ($score >= 3.5) $predikat = 'Baik (B)';
                elseif ($score >= 2.5) $predikat = 'Cukup (C)';
                elseif ($score >= 1.5) $predikat = 'Kurang (D)';
                else $predikat = 'Sangat Kurang (E)';
            }
        @endphp
        <div class="score-badge">PREDIKAT: {{ strtoupper($predikat) }}</div>
    </div>

    <!-- Indicator Breakdown Table -->
    <div style="font-weight: bold; margin-bottom: 6px; font-size: 11px; color: #2b6cb0;">RINCIAN EVALUASI INDIKATOR KPI:</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="32%">Indikator KPI & Rumus Ukur</th>
                <th width="7%" class="text-center">Bobot</th>
                <th width="10%" class="text-center">Frekuensi</th>
                <th width="17%">Target</th>
                <th width="15%">Skor Terpilih</th>
                <th width="15%">Catatan Evaluasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($templateIndicators as $index => $ti)
                @php
                    $scoreItem = collect($scores)->firstWhere('template_indicator_id', $ti->id);
                    $levelId = $scoreItem->rating_level_id ?? null;
                    $levelObj = collect($ratingLevels)->firstWhere('id', $levelId);
                    $rawScore = $scoreItem->raw_score ?? null;
                    $weightedScore = $scoreItem->weighted_score ?? ($rawScore !== null ? round($rawScore * ($ti->weight / 100), 2) : null);
                    $calcMethod = $ti->calculation_method ?: ($ti->indicator->calculation_method ?? null);
                    $freq = $ti->frequency ?: ($ti->indicator->frequency ?? '-');
                    $freqMap = ['B' => 'Bulanan (B)', 'M' => 'Mingguan (M)', 'H' => 'Harian (H)', 'K' => 'Kuartalan (K)', 'I' => 'Insidental (I)'];
                    $freqLabel = $freqMap[strtoupper(trim($freq))] ?? $freq;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <b>{{ $ti->indicator->name ?? '-' }}</b>
                        @if($calcMethod)
                            <br><span style="color: #4a5568; font-size: 8.5px; font-style: italic;">Rumus: {{ $calcMethod }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $ti->weight }}%</td>
                    <td class="text-center">{{ $freqLabel }}</td>
                    <td>{{ $ti->target ?? '—' }}</td>
                    <td>
                        @if($levelObj)
                            <div>{{ $levelObj->label }} ({{ $levelObj->score }})</div>
                        @elseif($rawScore !== null)
                            <div>Skor: <b>{{ $rawScore }}</b></div>
                        @else
                            —
                        @endif
                        @if($weightedScore !== null)
                            <div style="color: #2b6cb0; font-size: 8.5px;">Tertimbang: {{ $weightedScore }}</div>
                        @endif
                    </td>
                    <td>{{ $scoreItem->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada indikator dalam template ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Reviewer Notes -->
    @if($assessment->reviewer_notes)
        <div class="notes-box">
            <div class="notes-title">CATATAN & FEEDBACK PENILAI:</div>
            <div>"{{ $assessment->reviewer_notes }}"</div>
        </div>
    @endif

    <!-- Signatures -->
    <table class="signatures">
        <tr>
            <td>
                Karyawan Yang Dinilai,<br><br><br><br>
                <div class="sig-line">{{ $employee->name ?? 'Karyawan' }}</div>
                <div style="font-size: 9px; color: #718096; margin-top: 2px;">NIP: {{ $employee->employee_id ?? '-' }}</div>
            </td>
            <td>
                Atasan / Evaluator,<br><br><br><br>
                <div class="sig-line">{{ $reviewer->name ?? 'Evaluator' }}</div>
                <div style="font-size: 9px; color: #718096; margin-top: 2px;">Penilai</div>
            </td>
            <td>
                Mengetahui (HRD),<br><br><br><br>
                <div class="sig-line">HR Department</div>
                <div style="font-size: 9px; color: #718096; margin-top: 2px;">HRD Manager</div>
            </td>
        </tr>
    </table>

</body>
</html>

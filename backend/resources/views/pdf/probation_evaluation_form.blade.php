<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Form Penilaian Karyawan Probation - {{ $employee->name ?? 'Employee' }}</title>
    <style>
        @page {
            margin: 12mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 5px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .header-title {
            text-align: center;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .company-desc {
            font-size: 11px;
            font-weight: bold;
        }
        .company-sub {
            font-size: 9px;
        }
        .form-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 8px 0;
            text-decoration: underline;
        }
        .section-header {
            background-color: #d9d9d9;
            font-weight: bold;
            padding: 3px 5px;
            border: 1px solid #000;
        }
        .bordered-table {
            border: 1px solid #000;
            margin-bottom: 8px;
        }
        .bordered-table th, .bordered-table td {
            border: 1px solid #000;
            padding: 3px 5px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .bg-gray { background-color: #f2f2f2; }
        .bg-dark-gray { background-color: #d9d9d9; }
        
        .box-title {
            background-color: #d9d9d9;
            font-weight: bold;
            border: 1px solid #000;
            padding: 3px;
            margin-top: 5px;
        }
        .box-content {
            border: 1px solid #000;
            border-top: none;
            padding: 5px;
            min-height: 50px;
        }
        .sig-box {
            height: 50px;
        }
    </style>
</head>
<body>

    <!-- Header Section (Kop Surat Resmi) -->
    <table class="header-table" style="width: 100%; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 8px;">
        <tr>
            <td style="width: 20%; vertical-align: middle; text-align: center;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="max-height: 55px; max-width: 120px; object-fit: contain;" alt="Logo" />
                @else
                    <div style="font-size: 15px; font-weight: bold; background-color: #0f172a; color: #fff; padding: 6px 10px; border-radius: 4px; text-transform: uppercase;">
                        {{ $company->name ?? 'HRMS' }}
                    </div>
                @endif
            </td>
            <td class="header-title" style="width: 80%; vertical-align: middle; text-align: center; padding-right: 15px;">
                <div class="company-name" style="font-size: 16pt; font-weight: bold; text-transform: uppercase; color: #0f172a; letter-spacing: 1px;">
                    {{ $company->name ?? 'JANGEBY GROUP' }}
                </div>
                @if(!empty($company->legal_name) && $company->legal_name !== $company->name)
                    <div class="company-desc" style="font-size: 9.5pt; font-weight: bold; color: #334155; margin-top: 1px;">
                        {{ $company->legal_name }}
                    </div>
                @endif
                <div class="company-sub" style="font-size: 8.5pt; color: #475569; margin-top: 2px;">
                    {{ $company->address ?? 'Gedung Operasional HRMS' }}
                </div>
                <div class="company-sub" style="font-size: 8.5pt; color: #475569;">
                    @if(!empty($company->phone)) Telp: {{ $company->phone }} @endif
                    @if(!empty($company->email)) &nbsp;|&nbsp; Email: {{ $company->email }} @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="form-title">FORM PENILAIAN KARYAWAN PROBATION</div>

    <!-- Data Karyawan & Absensi / Pelanggaran -->
    <table style="margin-bottom: 8px;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 5px;">
                <table class="bordered-table" style="margin-bottom: 0;">
                    <tr>
                        <td colspan="3" class="section-header">Data Karyawan</td>
                    </tr>
                    <tr>
                        <td style="width: 35%;">Nama Karyawan</td>
                        <td style="width: 5%;">:</td>
                        <td style="width: 60%; font-weight: bold;">{{ $employee->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>NIK</td>
                        <td>:</td>
                        <td>{{ $employee->nip ?? $employee->employee_id ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Perusahaan</td>
                        <td>:</td>
                        <td><strong>{{ $company->name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td>:</td>
                        <td>{{ $assignment->position->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Departemen</td>
                        <td>:</td>
                        <td>{{ $assignment->department->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Atasan Langsung</td>
                        <td>:</td>
                        <td>{{ $evaluator->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Tgl Mulai Bekerja (Join)</td>
                        <td>:</td>
                        <td>{{ $evaluation->probation_start_date ? \Carbon\Carbon::parse($evaluation->probation_start_date)->format('d/m/Y') : ($assignment->join_date ? \Carbon\Carbon::parse($assignment->join_date)->format('d/m/Y') : '-') }}</td>
                    </tr>
                    <tr>
                        <td>Masa Probation</td>
                        <td>:</td>
                        <td>{{ $evaluation->probation_period_text ?? '3 Bulan' }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal Penilaian</td>
                        <td>:</td>
                        <td>{{ $evaluation->evaluation_date ? \Carbon\Carbon::parse($evaluation->evaluation_date)->format('d/m/Y') : date('d/m/Y') }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 45%; vertical-align: top; padding-left: 5px;">
                <!-- Periode Absensi -->
                <table class="bordered-table" style="margin-bottom: 8px;">
                    <tr class="bg-dark-gray text-center text-bold">
                        <td colspan="5">
                            Periode Absensi
                            @if($evaluation->probation_start_date && $evaluation->evaluation_date)
                                <div style="font-size: 7.5pt; font-weight: normal; color: #1e293b; margin-top: 1px;">
                                    ({{ \Carbon\Carbon::parse($evaluation->probation_start_date)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($evaluation->evaluation_date)->format('d/m/Y') }})
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr class="bg-gray text-center" style="font-size: 9px;">
                        <td style="width: 20%;">Alpa</td>
                        <td style="width: 20%;">Ijin</td>
                        <td style="width: 20%;">Surat Dokter</td>
                        <td style="width: 20%;">Pulang ½ hari</td>
                        <td style="width: 20%;">Pulang kesiangan</td>
                    </tr>
                    <tr class="text-center">
                        <td>{{ $evaluation->absent_days ?? 0 }}</td>
                        <td>{{ $evaluation->permit_days ?? 0 }}</td>
                        <td>{{ $evaluation->sick_days ?? 0 }}</td>
                        <td>{{ $evaluation->half_days ?? 0 }}</td>
                        <td>{{ $evaluation->late_days ?? 0 }}</td>
                    </tr>
                </table>

                <!-- Catatan Pelanggaran -->
                <table class="bordered-table" style="margin-bottom: 0;">
                    <tr class="bg-dark-gray text-bold">
                        <td colspan="2">Catatan Pelanggaran [ √ ]</td>
                    </tr>
                    <tr>
                        <td style="width: 70%;">Peringatan lisan</td>
                        <td class="text-center" style="width: 30%;">{{ $evaluation->has_oral_warning ? '√' : '' }}</td>
                    </tr>
                    <tr>
                        <td>SP-1</td>
                        <td class="text-center">{{ $evaluation->has_sp1 ? '√' : '' }}</td>
                    </tr>
                    <tr>
                        <td>SP-2</td>
                        <td class="text-center">{{ $evaluation->has_sp2 ? '√' : '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Tabel Penilaian -->
    <table class="bordered-table">
        <thead>
            <tr class="bg-dark-gray text-bold">
                <td style="width: 5%;" class="text-center">No</td>
                <td style="width: 45%;" class="text-center">Aspek Penilaian</td>
                <td style="width: 10%;" class="text-center">Bobot (%)</td>
                <td style="width: 10%;" class="text-center">Nilai (1-5)</td>
                <td style="width: 12%;" class="text-center">Skor (Bobot x Nilai)</td>
                <td style="width: 18%;" class="text-center">Catatan</td>
            </tr>
            <tr class="bg-gray text-center" style="font-size: 8px; font-style: italic;">
                <td></td>
                <td></td>
                <td>a</td>
                <td>b</td>
                <td>a x b</td>
                <td></td>
            </tr>
        </thead>
        <tbody>
            @php
                $aspectList = is_array($aspects) ? $aspects : [];
                $grouped = [];
                foreach ($aspectList as $asp) {
                    $cat = $asp['category'] ?? 'Lain-lain';
                    $grouped[$cat][] = $asp;
                }
            @endphp

            @foreach($grouped as $categoryName => $items)
                <tr class="bg-gray text-bold">
                    <td colspan="6">{{ $categoryName }}</td>
                </tr>
                @foreach($items as $idx => $item)
                    <tr>
                        <td class="text-center"></td>
                        <td>{{ $item['name'] ?? '-' }}</td>
                        <td class="text-center">{{ $item['weight'] ?? 10 }}</td>
                        <td class="text-center">{{ $item['rating'] ?? 0 }}</td>
                        <td class="text-center font-bold">{{ isset($item['score']) ? number_format($item['score'], 0) : (($item['weight'] ?? 10) * ($item['rating'] ?? 0)) }}</td>
                        <td>{{ $item['notes'] ?? '' }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="bg-dark-gray text-bold">
                <td colspan="2" class="text-left">TOTAL 100</td>
                <td class="text-center">100</td>
                <td class="text-center"></td>
                <td class="text-center">{{ number_format($evaluation->total_weighted_score ?? 0, 0) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Table Scale & Score Summary -->
    <table style="margin-bottom: 8px;">
        <tr>
            <td style="width: 35%; vertical-align: top; padding-right: 5px;">
                <table class="bordered-table" style="font-size: 9px; margin-bottom: 0;">
                    <tr class="bg-dark-gray text-bold text-center">
                        <td colspan="2">Skala Penilaian</td>
                    </tr>
                    <tr class="bg-gray text-bold text-center">
                        <td style="width: 30%;">Nilai</td>
                        <td style="width: 70%;">Keterangan</td>
                    </tr>
                    <tr><td class="text-center">5</td><td>Sangat Baik</td></tr>
                    <tr><td class="text-center">4</td><td>Baik</td></tr>
                    <tr><td class="text-center">3</td><td>Cukup</td></tr>
                    <tr><td class="text-center">2</td><td>Kurang</td></tr>
                    <tr><td class="text-center">1</td><td>Sangat Kurang</td></tr>
                </table>
            </td>
            <td style="width: 40%; vertical-align: top; padding-left: 5px; padding-right: 5px;">
                <table class="bordered-table" style="font-size: 9px; margin-bottom: 0;">
                    <tr class="bg-dark-gray text-bold text-center">
                        <td colspan="2">Total Skor</td>
                    </tr>
                    <tr class="bg-gray text-bold text-center">
                        <td style="width: 40%;">Nilai</td>
                        <td style="width: 60%;">Total Skor</td>
                    </tr>
                    <tr><td>Sangat Baik</td><td class="text-center">85 – 100</td></tr>
                    <tr><td>Baik</td><td class="text-center">70 – 84</td></tr>
                    <tr><td>Cukup</td><td class="text-center">60 – 69</td></tr>
                    <tr><td>Kurang</td><td class="text-center">50 – 59</td></tr>
                    <tr><td>Sangat Kurang</td><td class="text-center">&lt; 50</td></tr>
                </table>
            </td>
            <td style="width: 25%; vertical-align: middle; text-align: center;">
                <div style="border: 1px solid #000; padding: 10px; background-color: #f9f9f9;">
                    <div style="font-size: 18px; font-weight: bold;">==&gt;</div>
                    <div style="font-size: 11px; margin-top: 5px;">Total / 5 =</div>
                    <div style="font-size: 20px; font-weight: bold; color: #000; margin-top: 5px;">
                        {{ number_format($evaluation->final_score_hundred ?? 0, 1) }}
                    </div>
                    <div style="font-size: 10px; font-weight: bold; margin-top: 3px;">
                        ({{ $evaluation->predicate ?? '-' }})
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Komentar Atasan -->
    <table style="margin-bottom: 8px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 4px;">
                <div class="box-title">Komentar Atasan</div>
                <div class="box-title bg-gray" style="border-top: none;">Kelebihan Karyawan :</div>
                <div class="box-content">
                    {!! nl2br(e($evaluation->strengths ?? '-')) !!}
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 4px;">
                <div class="box-title" style="visibility: hidden;">Spacer</div>
                <div class="box-title bg-gray" style="border-top: 1px solid #000;">Area yang perlu ditingkatkan :</div>
                <div class="box-content">
                    {!! nl2br(e($evaluation->improvements ?? '-')) !!}
                </div>
            </td>
        </tr>
    </table>

    <!-- Keputusan Akhir & Catatan Keputusan -->
    <table class="bordered-table" style="margin-bottom: 8px;">
        <tr class="bg-dark-gray text-bold">
            <td>Keputusan Akhir :</td>
        </tr>
        <tr>
            <td style="padding: 6px 10px; font-weight: bold; font-size: 11px;">
                @if(($evaluation->final_decision ?? '') == 'passed_permanent')
                    [ √ ] Diangkat Menjadi Karyawan Tetap &nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp; ] Perpanjang Masa Probation &nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp; ] Diberhentikan / Putus Hubungan Kerja
                @elseif(($evaluation->final_decision ?? '') == 'extend_probation')
                    [ &nbsp; ] Diangkat Menjadi Karyawan Tetap &nbsp;&nbsp;&nbsp;&nbsp; [ √ ] Perpanjang Masa Probation &nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp; ] Diberhentikan / Putus Hubungan Kerja
                @else
                    [ &nbsp; ] Diangkat Menjadi Karyawan Tetap &nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp; ] Perpanjang Masa Probation &nbsp;&nbsp;&nbsp;&nbsp; [ √ ] Diberhentikan / Putus Hubungan Kerja
                @endif
            </td>
        </tr>
    </table>

    <table class="bordered-table" style="margin-bottom: 12px;">
        <tr class="bg-dark-gray text-bold">
            <td>Catatan Keputusan :</td>
        </tr>
        <tr>
            <td style="height: 35px; vertical-align: top; padding: 5px;">
                {!! nl2br(e($evaluation->decision_notes ?? '-')) !!}
            </td>
        </tr>
    </table>

    <!-- Tanggal & Signatures Table -->
    <div style="margin-bottom: 5px;">Tanggal : {{ $evaluation->evaluation_date ? \Carbon\Carbon::parse($evaluation->evaluation_date)->format('d F Y') : date('d F Y') }}</div>
    <table class="bordered-table">
        <tr class="bg-gray text-center text-bold">
            <td style="width: 33%;">Atasan Langsung</td>
            <td style="width: 33%;">Manager Dept.</td>
            <td style="width: 34%;">Manager HRD</td>
        </tr>
        <tr>
            <td class="sig-box text-center" style="vertical-align: bottom;">
                ( {{ $evaluator->name ?? '.......................' }} )
            </td>
            <td class="sig-box text-center" style="vertical-align: bottom;">
                ( {{ $evaluation->approvedByDeptManager->name ?? '.......................' }} )
            </td>
            <td class="sig-box text-center" style="vertical-align: bottom;">
                ( {{ $evaluation->approvedByHrdManager->name ?? '.......................' }} )
            </td>
        </tr>
        <tr style="font-size: 9px;">
            <td>Nama : {{ $evaluator->name ?? '-' }}<br>Tanggal : </td>
            <td>Nama : {{ $evaluation->approvedByDeptManager->name ?? '-' }}<br>Tanggal : </td>
            <td>Nama : {{ $evaluation->approvedByHrdManager->name ?? '-' }}<br>Tanggal : </td>
        </tr>
    </table>

</body>
</html>

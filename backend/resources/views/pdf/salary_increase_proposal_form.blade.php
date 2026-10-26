<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Permohonan Kenaikan Gaji - {{ $employee->name ?? 'Employee' }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 10px;
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
            margin: 15px 0 20px 0;
            text-decoration: underline;
        }
        .section-title {
            font-weight: bold;
            font-size: 12px;
            margin-top: 15px;
            margin-bottom: 8px;
        }
        .meta-table {
            margin-left: 15px;
            margin-bottom: 15px;
            width: 95%;
        }
        .meta-table td {
            padding: 3px 5px;
        }
        .salary-table {
            border: 1px solid #000;
            font-size: 10px;
        }
        .salary-table th, .salary-table td {
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .salary-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .reasons-list {
            margin-left: 15px;
            margin-bottom: 20px;
        }
        .reasons-list td {
            padding: 4px 5px;
        }

        .sig-section {
            margin-top: 25px;
            width: 100%;
        }
        .sig-section td {
            text-align: center;
            vertical-align: top;
            padding: 5px;
        }
        .sig-space {
            height: 55px;
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

    <div class="form-title">PERMOHONAN KENAIKAN GAJI</div>

    <!-- A. LATAR BELAKANG -->
    <div class="section-title">A. LATAR BELAKANG</div>
    <table class="meta-table">
        <tr>
            <td style="width: 25%;">1. Nama</td>
            <td style="width: 3%;">:</td>
            <td style="width: 72%; font-weight: bold;">{{ $employee->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>2. Nomor Induk</td>
            <td>:</td>
            <td>{{ $employee->nip ?? $employee->employee_id ?? '-' }}</td>
        </tr>
        <tr>
            <td>3. Jabatan</td>
            <td>:</td>
            <td>{{ $assignment->position->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>4. Departemen</td>
            <td>:</td>
            <td>{{ $assignment->department->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>5. Divisi</td>
            <td>:</td>
            <td>{{ $assignment->division->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>6. Tanggal Masuk Kerja</td>
            <td>:</td>
            <td>{{ $evaluation->probation_start_date ? \Carbon\Carbon::parse($evaluation->probation_start_date)->format('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td>7. Masa Kerja</td>
            <td>:</td>
            <td>{{ $proposal->employment_duration_text ?? $evaluation->probation_period_text ?? '3 Bulan' }}</td>
        </tr>
    </table>

    <!-- B. USULAN KENAIKAN GAJI -->
    <div class="section-title">B. USULAN KENAIKAN GAJI</div>
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 32%; vertical-align: top; padding-right: 5px;">
                <table class="salary-table">
                    <thead>
                        <tr>
                            <th colspan="2">GAJI SAAT INI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Gaji Harian</td>
                            <td class="text-right">Rp. {{ number_format($proposal->current_daily_salary ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Gaji Bulanan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->current_monthly_salary ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Bonus Kerajinan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->current_attendance_bonus ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Jabatan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->current_position_allowance ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Leader</td>
                            <td class="text-right">Rp. {{ number_format($proposal->current_leader_allowance ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 36%; vertical-align: top; padding-left: 5px; padding-right: 5px;">
                <table class="salary-table">
                    <thead>
                        <tr>
                            <th colspan="2">USULAN KENAIKAN GAJI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Gaji Harian</td>
                            <td class="text-right">Rp. {{ number_format($proposal->proposed_daily_salary_increase ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Gaji Bulanan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->proposed_monthly_salary_increase ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Bonus Kerajinan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->proposed_attendance_bonus_increase ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Jabatan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->proposed_position_allowance_increase ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Leader</td>
                            <td class="text-right">Rp. {{ number_format($proposal->proposed_leader_allowance_increase ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 32%; vertical-align: top; padding-left: 5px;">
                <table class="salary-table">
                    <thead>
                        <tr>
                            <th colspan="2">GAJI BARU</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Gaji Harian</td>
                            <td class="text-right">Rp. {{ number_format($proposal->new_daily_salary ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Gaji Bulanan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->new_monthly_salary ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Bonus Kerajinan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->new_attendance_bonus ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Jabatan</td>
                            <td class="text-right">Rp. {{ number_format($proposal->new_position_allowance ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Leader</td>
                            <td class="text-right">Rp. {{ number_format($proposal->new_leader_allowance ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- C. ALASAN KENAIKAN GAJI -->
    <div class="section-title">C. ALASAN KENAIKAN GAJI</div>
    @php
        $reasons = is_array($proposal->reasons) ? $proposal->reasons : [];
    @endphp
    <table class="reasons-list">
        @for($i = 0; $i < 5; $i++)
            <tr>
                <td style="width: 3%;">{{ $i + 1 }}.</td>
                <td style="width: 97%; border-bottom: 1px dotted #888;">
                    {{ $reasons[$i] ?? '' }}
                </td>
            </tr>
        @endfor
    </table>

    <!-- Closing & Signatures -->
    <div style="margin-top: 15px;">Demikian permohonan ini saya ajukan,</div>
    <div style="margin-bottom: 15px;">Depok, {{ $proposal->proposal_date ? \Carbon\Carbon::parse($proposal->proposal_date)->format('d F Y') : date('d F Y') }}</div>

    <table class="sig-section">
        <tr>
            <td style="width: 33%;">
                <b>Hormat Saya,</b><br>
                Yang Mengusulkan,<br>
                <div class="sig-space"></div>
                ( {{ $proposal->proposedBy->name ?? '....................................' }} )
            </td>
            <td style="width: 33%;">
                <br><b>Mengetahui,</b><br>
                <div class="sig-space"></div>
                ( {{ $proposal->areaManager->name ?? '....................................' }} )<br>
                <b>Manager Area</b>
            </td>
            <td style="width: 34%;">
                <br><b>Mengetahui,</b><br>
                <div class="sig-space"></div>
                ( {{ $proposal->accountingManager->name ?? '....................................' }} )<br>
                <b>Manager Accounting</b>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding-top: 20px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; text-align: center;">
                            <b>Menyetujui,</b><br><br>
                            <div class="sig-space"></div>
                            ( {{ $proposal->hrdManager->name ?? '....................................' }} )<br>
                            <b>Manager HRD</b>
                        </td>
                        <td style="width: 50%; text-align: center;">
                            <br><br>
                            <div class="sig-space"></div>
                            ( {{ $proposal->director->name ?? '....................................' }} )<br>
                            <b>Direktur Utama</b>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $run->employee->name ?? 'Karyawan' }}</title>
    <style>
        @page {
            margin: 20px 25px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            background-color: #1a3c5e;
            border-bottom: 3px solid #008779;
            padding: 10px 14px 8px 14px;
            margin-bottom: 12px;
            border-radius: 4px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .company-branch-tag {
            font-size: 8pt;
            font-weight: bold;
            color: #a7f3d0;
            background-color: #005a51;
            border: 1px solid #008779;
            padding: 2px 7px;
            border-radius: 3px;
            margin-left: 6px;
            text-transform: none;
            display: inline-block;
            vertical-align: middle;
        }
        .company-info {
            font-size: 7.5pt;
            color: #e2e8f0;
            line-height: 1.3;
            margin-top: 2px;
        }
        .slip-title-container {
            text-align: right;
            width: 38%;
            vertical-align: middle;
        }
        .slip-title {
            font-size: 11.5pt;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1px;
            line-height: 1.2;
        }
        .slip-period {
            font-size: 7.5pt;
            background-color: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 3px;
            line-height: 1.25;
            text-align: right;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 12px;
        }
        .info-table {
            width: 100%;
            font-size: 8.5pt;
        }
        .info-table td {
            padding: 2px 3px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #475569;
            width: 17%;
        }
        .info-colon {
            width: 2%;
        }
        .info-value {
            width: 31%;
            color: #0f172a;
        }

        .details-container {
            width: 100%;
            margin-bottom: 12px;
        }
        .details-col {
            width: 49%;
            float: left;
        }
        .details-col.right {
            float: right;
        }
        .table-header {
            font-size: 9pt;
            font-weight: bold;
            padding: 5px 8px;
            color: #ffffff;
            border-radius: 4px 4px 0 0;
        }
        .earning-header {
            background-color: #059669;
        }
        .deduction-header {
            background-color: #dc2626;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            border: 1px solid #e2e8f0;
        }
        .details-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .details-table td.amount {
            text-align: right;
            font-weight: 600;
        }
        .details-table tfoot td {
            font-weight: bold;
            background-color: #f8fafc;
            border-top: 2px solid #e2e8f0;
        }
        .clear {
            clear: both;
        }

        .bonus-box {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 10px;
            margin-bottom: 12px;
        }
        .bonus-box-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #166534;
            margin-bottom: 4px;
        }
        .bonus-item {
            font-size: 8pt;
            color: #14532d;
            padding: 2px 0;
            border-top: 1px dashed #bbf7d0;
        }
        .bonus-item:first-child {
            border-top: none;
        }

        .deduction-box {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 10px;
            margin-bottom: 12px;
        }
        .deduction-box-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #991b1b;
            margin-bottom: 4px;
        }
        .deduction-item {
            font-size: 8pt;
            color: #7f1d1d;
            padding: 2px 0;
            border-top: 1px dashed #fecaca;
        }
        .deduction-item:first-child {
            border-top: none;
        }

        .summary-card {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .summary-table {
            width: 100%;
            font-size: 9.5pt;
        }
        .summary-table td {
            padding: 2px 0;
        }
        .thp-label {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a8a;
        }
        .thp-amount {
            font-size: 13pt;
            font-weight: bold;
            color: #2563eb;
            text-align: right;
        }

        .page-break {
            page-break-before: always;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-top: 8px;
            border: 1px solid #cbd5e1;
        }
        .attendance-table th {
            background-color: #0f766e;
            color: #ffffff;
            font-weight: bold;
            padding: 5px 6px;
            border: 1px solid #0d9488;
            text-align: left;
        }
        .attendance-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
        }
        .attendance-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .badge-status {
            font-weight: bold;
            display: inline-block;
        }
        .status-present { color: #059669; }
        .status-late { color: #d97706; }
        .status-holiday { color: #0284c7; }
        .status-leave { color: #7c3aed; }
        .status-absent { color: #dc2626; }

        .confidential {
            font-size: 8pt;
            color: #64748b;
            text-align: center;
            margin-top: 18px;
            font-style: italic;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <!-- ============================================================== -->
    <!-- HALAMAN 1: SLIP GAJI KARYAWAN -->
    <!-- ============================================================== -->

    @php
        $companyId = $run->assignment?->company_id ?? $run->period?->company_id ?? $run->assignment?->placement_company_id;
        $company = $companyId ? \App\Models\Company::find($companyId) : null;
        if (!$company) {
            $company = $run->assignment?->company ?? $run->employee?->company;
        }
        $branch = $run->assignment?->branch ?? $run->period?->branch ?? $run->employee?->branch;
        $branchName = $branch?->name;
        $branchAddress = $branch?->address;
        $branchPhone = $branch?->phone;
        $branchEmail = $branch?->email;

        $logoBase64 = null;
        if ($branch && method_exists($branch, 'getEffectiveLogoBase64Attribute')) {
            $logoBase64 = $branch->effective_logo_base64;
        } elseif ($company && method_exists($company, 'getEffectiveLogoBase64Attribute')) {
            $logoBase64 = $company->effective_logo_base64;
        }
        if (!$logoBase64) {
            $group = \App\Models\CompanyGroup::first();
            if ($group && $group->logo && file_exists(storage_path('app/public/' . $group->logo))) {
                $p = storage_path('app/public/' . $group->logo);
                $mime = mime_content_type($p) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
            }
        }
        $companyName = $company?->name ?? 'PERUSAHAAN';
        $displayAddress = $branchAddress ?: ($company?->address ?? '');
        $displayPhone = $branchPhone ?: ($company?->phone ?? '');
        $displayEmail = $branchEmail ?: ($company?->email ?? '');
    @endphp

    <!-- Header Perusahaan & Judul -->
    <table class="header-table">
        <tr>
            @if($logoBase64)
            <td style="width: 70px; vertical-align: middle; padding-right: 10px;">
                <img src="{{ $logoBase64 }}" style="max-height: 44px; max-width: 65px; display: block;" alt="Logo" />
            </td>
            @endif
            <td style="vertical-align: middle;">
                <div class="company-name">
                    {{ $companyName }}
                    @if($branchName)
                        <span class="company-branch-tag">{{ $branchName }}</span>
                    @endif
                </div>
                @if($displayAddress)
                <div class="company-info">{{ $displayAddress }}</div>
                @endif
                @if($displayPhone || $displayEmail)
                <div class="company-info">
                    @if($displayPhone)Telp: {{ $displayPhone }}@endif
                    @if($displayPhone && $displayEmail) | @endif
                    @if($displayEmail)Email: {{ $displayEmail }}@endif
                </div>
                @endif
            </td>
            <td class="slip-title-container" style="vertical-align: middle;">
                <div class="slip-title">SLIP GAJI KARYAWAN</div>
                <div class="slip-period">
                    <div style="font-weight: bold;">PERIODE: {{ $run->period->name ?? 'Payroll' }}</div>
                    <div>{{ \Carbon\Carbon::parse($run->period->start_date ?? now())->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($run->period->end_date ?? now())->format('d/m/Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

    @php
        $breakdownData = is_array($run->breakdown) ? $run->breakdown : (json_decode($run->breakdown ?? '[]', true) ?? []);
        $att = $attendanceSummary ?? ($breakdownData['attendance'] ?? []);
        $vars = $breakdownData['variables'] ?? [];
        $totalPeriodDays = $att['total_period_days'] ?? $vars['total_period_days'] ?? $vars['total_days_in_month'] ?? '-';
        $hadir = $att['hadir'] ?? $vars['total_attendance_days'] ?? 0;
        $libur = $att['libur'] ?? $vars['total_day_off'] ?? 0;
        $cuti  = $att['cuti']  ?? $vars['total_paid_leave_days'] ?? 0;
        $alpha = $att['alpha'] ?? $vars['total_unpaid_leave_days'] ?? 0;
        $ratePerDay = $att['rate_per_day'] ?? $vars['rate_per_day'] ?? 0;
        $ratePerHour = $att['rate_per_hour'] ?? $vars['rate_per_hour'] ?? 0;
    @endphp

    <!-- Info Karyawan & Absensi Periode -->
    <div class="info-card">
        <table class="info-table">
            <tr>
                <td class="info-label">NIK / Kode</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $run->employee->employee_id ?? $run->employee->nik ?? '-' }}</td>
                
                <td class="info-label">Total Hari Periode</td>
                <td class="info-colon">:</td>
                <td class="info-value"><strong>{{ $totalPeriodDays }} Hari</strong></td>
            </tr>
            <tr>
                <td class="info-label">Nama Karyawan</td>
                <td class="info-colon">:</td>
                <td class="info-value"><strong>{{ $run->employee->name }}</strong></td>
                
                <td class="info-label">Kehadiran Periode</td>
                <td class="info-colon">:</td>
                <td class="info-value">
                    <strong style="color: #059669;">{{ $hadir }}</strong> Masuk / 
                    <strong>{{ $libur }}</strong> Libur / 
                    <strong>{{ $cuti }}</strong> Cuti
                    @if($alpha > 0)
                        / <span style="color: #dc2626; font-weight: bold;">{{ $alpha }} Alpha</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="info-label">Departemen / Jabatan</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $run->employee->department->name ?? '-' }} / {{ $run->employee->position->name ?? '-' }}</td>
                
                <td class="info-label">Cabang</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $branchName ?? $run->employee->branch->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Status Karyawan</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ ucfirst($run->employee->employment_status ?? '-') }}</td>

                <td class="info-label">Tgl Bergabung</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $run->employee->join_date ? \Carbon\Carbon::parse($run->employee->join_date)->format('d/m/Y') : '-' }}</td>
            </tr>
            @if(($componentStatus['has_bpjs'] ?? false) || ($componentStatus['has_tax'] ?? false))
            <tr>
                @if($componentStatus['has_bpjs_kesehatan'] ?? false)
                    <td class="info-label">BPJS Kesehatan</td>
                    <td class="info-colon">:</td>
                    <td class="info-value">{{ $run->employee->bpjs_kesehatan ?? '-' }}</td>
                @elseif($componentStatus['has_tax'] ?? false)
                    <td class="info-label">NPWP</td>
                    <td class="info-colon">:</td>
                    <td class="info-value">{{ $run->employee->npwp ?? '-' }}</td>
                @else
                    <td class="info-label"></td><td class="info-colon"></td><td class="info-value"></td>
                @endif

                @if($componentStatus['has_bpjs_ketenagakerjaan'] ?? false)
                    <td class="info-label">BPJS Ketenagakerjaan</td>
                    <td class="info-colon">:</td>
                    <td class="info-value">{{ $run->employee->bpjs_ketenagakerjaan ?? '-' }}</td>
                @elseif(($componentStatus['has_bpjs_kesehatan'] ?? false) && ($componentStatus['has_tax'] ?? false))
                    <td class="info-label">NPWP</td>
                    <td class="info-colon">:</td>
                    <td class="info-value">{{ $run->employee->npwp ?? '-' }}</td>
                @else
                    <td class="info-label"></td><td class="info-colon"></td><td class="info-value"></td>
                @endif
            </tr>
            @endif
        </table>
    </div>

    <!-- Rincian Pendapatan & Potongan -->
    <div class="details-container">
        <!-- Pendapatan -->
        <div class="details-col">
            <div class="table-header earning-header">PENERIMAAN (EARNINGS)</div>
            <table class="details-table">
                <tbody>
                    @php $totalEarnings = 0; @endphp
                    @foreach($run->details as $detail)
                        @if($detail->type === 'earning' || $detail->type === 'benefit')
                        @php $totalEarnings += $detail->amount; @endphp
                        <tr>
                            <td>{{ $detail->label }}</td>
                            <td class="amount">Rp {{ number_format($detail->amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    @endforeach
                    @if($totalEarnings == 0 && $run->gross_salary > 0)
                        @php $totalEarnings = $run->gross_salary; @endphp
                        <tr>
                            <td>Gaji Gross</td>
                            <td class="amount">Rp {{ number_format($run->gross_salary, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td>TOTAL PENERIMAAN</td>
                        <td class="amount" style="color: #059669;">Rp {{ number_format($totalEarnings, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Potongan -->
        <div class="details-col right">
            <div class="table-header deduction-header">POTONGAN (DEDUCTIONS)</div>
            <table class="details-table">
                <tbody>
                    @php $totalDeductions = 0; @endphp
                    @foreach($run->details as $detail)
                        @if(in_array($detail->type, ['deduction', 'tax', 'bpjs']))
                        @php $totalDeductions += abs($detail->amount); @endphp
                        <tr>
                            <td>{{ $detail->label }}</td>
                            <td class="amount" style="color: #dc2626;">Rp {{ number_format(abs($detail->amount), 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    @endforeach
                    @if($totalDeductions == 0 && ($run->total_deduction > 0 || $run->total_tax > 0 || $run->total_bpjs > 0))
                        @php $totalDeductions = $run->total_deduction + $run->total_tax + $run->total_bpjs; @endphp
                        <tr>
                            <td>Total Potongan System</td>
                            <td class="amount" style="color: #dc2626;">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td>TOTAL POTONGAN</td>
                        <td class="amount" style="color: #dc2626;">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Informasi Bonus & Insentif dari Inputan Bonus (Jika Ada) -->
    @if((isset($rewards) && count($rewards) > 0) || !empty($breakdownData['rewards']))
    @php
        $bonusList = isset($rewards) && count($rewards) > 0 ? $rewards : collect($breakdownData['rewards'] ?? []);
    @endphp
    <div class="bonus-box">
        <div class="bonus-box-title">INFORMASI BONUS & INSENTIF (DARI INPUTAN BONUS):</div>
        @foreach($bonusList as $item)
            @php
                $bTitle = is_array($item) ? ($item['title'] ?? 'Bonus') : ($item->title ?? 'Bonus');
                $bAmount = is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0);
                $bType = is_array($item) ? ($item['type'] ?? 'bonus') : ($item->type ?? 'bonus');
                $bDesc = is_array($item) ? ($item['description'] ?? null) : ($item->description ?? null);
            @endphp
            <div class="bonus-item">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 70%;">
                            • <strong>{{ $bTitle }}</strong> ({{ ucfirst($bType) }})
                            @if($bDesc) <span style="color: #4b5563;">— {{ $bDesc }}</span> @endif
                        </td>
                        <td style="width: 30%; text-align: right; font-weight: bold; color: #166534;">
                            Rp {{ number_format($bAmount, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Informasi Potongan Lain-Lain dari Inputan Potongan (Jika Ada) -->
    @if((isset($deductions) && count($deductions) > 0) || !empty($breakdownData['deductions']))
    @php
        $dedList = isset($deductions) && count($deductions) > 0 ? $deductions : collect($breakdownData['deductions'] ?? []);
    @endphp
    <div class="deduction-box">
        <div class="deduction-box-title">INFORMASI POTONGAN LAIN-LAIN (INPUTAN POTONGAN):</div>
        @foreach($dedList as $item)
            @php
                $dTitle = is_array($item) ? ($item['title'] ?? 'Potongan Lain-Lain') : ($item->title ?? 'Potongan Lain-Lain');
                $dAmount = is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0);
                $dType = is_array($item) ? ($item['type'] ?? 'other') : ($item->type ?? 'other');
                $dDesc = is_array($item) ? ($item['description'] ?? null) : ($item->description ?? null);
            @endphp
            <div class="deduction-item">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 70%;">
                            • <strong>{{ $dTitle }}</strong> ({{ ucfirst($dType) }})
                            @if($dDesc) <span style="color: #4b5563;">— {{ $dDesc }}</span> @endif
                        </td>
                        <td style="width: 30%; text-align: right; font-weight: bold; color: #dc2626;">
                            - Rp {{ number_format($dAmount, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Ringkasan THP -->
    <div class="summary-card">
        <table class="summary-table">
            <tr>
                <td style="width: 50%;">
                    <div>Total Penerimaan: <strong>Rp {{ number_format($totalEarnings, 0, ',', '.') }}</strong></div>
                    <div>Total Potongan: <strong style="color: #dc2626;">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</strong></div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="thp-label">GAJI BERSIH (TAKE HOME PAY)</div>
                    <div class="thp-amount">Rp {{ number_format($run->net_salary, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Info Catatan Resmi (Tanpa Tanda Tangan) -->
    <div class="confidential">
        * Dokumen ini diterbitkan secara elektronik oleh Sistem HRMS dan sah tanpa tanda tangan basah.<br>
        * Bersifat RAHASIA, hanya diperuntukkan bagi karyawan yang namanya tertera pada dokumen ini.
    </div>


    <!-- ============================================================== -->
    <!-- HALAMAN 2: REKAPITULASI & RINCIAN LOG ABSENSI KARYAWAN -->
    <!-- ============================================================== -->
    <div class="page-break"></div>

    <!-- Header Halaman 2 (KOP KONSISTEN) -->
    <table class="header-table">
        <tr>
            @if($logoBase64)
            <td style="width: 70px; vertical-align: middle; padding-right: 10px;">
                <img src="{{ $logoBase64 }}" style="max-height: 44px; max-width: 65px; display: block;" alt="Logo" />
            </td>
            @endif
            <td style="vertical-align: middle;">
                <div class="company-name">
                    {{ $companyName }}
                    @if($branchName)
                        <span class="company-branch-tag">{{ $branchName }}</span>
                    @endif
                </div>
                @if($displayAddress)
                <div class="company-info">{{ $displayAddress }}</div>
                @endif
                @if($displayPhone || $displayEmail)
                <div class="company-info">
                    @if($displayPhone)Telp: {{ $displayPhone }}@endif
                    @if($displayPhone && $displayEmail) | @endif
                    @if($displayEmail)Email: {{ $displayEmail }}@endif
                </div>
                @endif
            </td>
            <td class="slip-title-container" style="vertical-align: middle;">
                <div class="slip-title">REKAPITULASI & LOG ABSENSI</div>
                <div class="slip-period">
                    <div style="font-weight: bold;">PERIODE: {{ $run->period->name ?? 'Payroll' }}</div>
                    <div>{{ \Carbon\Carbon::parse($run->period->start_date ?? now())->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($run->period->end_date ?? now())->format('d/m/Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Info Karyawan & Ringkasan Halaman 2 -->
    <div class="info-card">
        <table class="info-table">
            <tr>
                <td class="info-label">NIK / Kode</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $run->employee->employee_id ?? $run->employee->nik ?? '-' }}</td>
                
                <td class="info-label">Total Periode</td>
                <td class="info-colon">:</td>
                <td class="info-value"><strong>{{ $totalPeriodDays }} Hari</strong></td>
            </tr>
            <tr>
                <td class="info-label">Nama Karyawan</td>
                <td class="info-colon">:</td>
                <td class="info-value"><strong>{{ $run->employee->name }}</strong></td>
                
                <td class="info-label">Hadir / Masuk</td>
                <td class="info-colon">:</td>
                <td class="info-value"><strong style="color: #059669;">{{ $hadir }} Hari</strong></td>
            </tr>
            <tr>
                <td class="info-label">Departemen / Jabatan</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $run->employee->department->name ?? '-' }} / {{ $run->employee->position->name ?? '-' }}</td>
                
                <td class="info-label">Libur / Cuti / Alpha</td>
                <td class="info-colon">:</td>
                <td class="info-value">
                    {{ $libur }} Libur / {{ $cuti }} Cuti 
                    @if($alpha > 0)
                        / <span style="color: #dc2626; font-weight: bold;">{{ $alpha }} Alpha</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="info-label">Cabang</td>
                <td class="info-colon">:</td>
                <td class="info-value">{{ $branchName ?? $run->employee->branch->name ?? '-' }}</td>

                <td class="info-label">Terlambat / Lembur</td>
                <td class="info-colon">:</td>
                <td class="info-value">
                    {{ $att['late_minutes'] ?? 0 }} menit / 
                    {{ $att['overtime_minutes'] ?? 0 }} menit
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabel Rincian Absensi Harian -->
    <div style="margin-top: 10px;">
        <div style="font-weight: bold; font-size: 9.5pt; color: #0f172a; margin-bottom: 5px;">
            RINCIAN LOG KEHADIRAN HARIAN PERIODE INI:
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 25px; text-align: center;">No</th>
                    <th style="width: 110px;">Tanggal & Hari</th>
                    <th style="width: 85px;">Shift</th>
                    <th style="width: 60px; text-align: center;">Masuk</th>
                    <th style="width: 60px; text-align: center;">Keluar</th>
                    <th style="width: 60px; text-align: center;">Terlambat</th>
                    <th style="width: 60px; text-align: center;">Lembur</th>
                    <th style="width: 65px; text-align: center;">Jam Kerja</th>
                    <th style="width: 75px; text-align: center;">Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($attendanceLogs) && count($attendanceLogs) > 0)
                    @foreach($attendanceLogs as $log)
                    @php
                        $st = strtolower($log['status'] ?? 'present');
                        $classStatus = 'status-present';
                        $statusLabel = 'Hadir';
                        if ($st === 'late') { $classStatus = 'status-late'; $statusLabel = 'Terlambat'; }
                        elseif ($st === 'holiday') { $classStatus = 'status-holiday'; $statusLabel = 'Libur'; }
                        elseif ($st === 'absent') { $classStatus = 'status-absent'; $statusLabel = 'Alpha'; }
                        elseif (in_array($st, ['leave','sick','permission'])) { $classStatus = 'status-leave'; $statusLabel = ucfirst($st); }
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $log['no'] }}</td>
                        <td>{{ $log['day_name'] }}, {{ $log['formatted_date'] }}</td>
                        <td>{{ $log['shift'] }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $log['clock_in'] }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $log['clock_out'] }}</td>
                        <td style="text-align: center; color: {{ ($log['late_minutes'] ?? 0) > 0 ? '#dc2626' : '#64748b' }};">
                            {{ ($log['late_minutes'] ?? 0) > 0 ? $log['late_minutes'] . ' mnt' : '-' }}
                        </td>
                        <td style="text-align: center; color: {{ ($log['overtime_minutes'] ?? 0) > 0 ? '#2563eb' : '#64748b' }};">
                            {{ ($log['overtime_minutes'] ?? 0) > 0 ? $log['overtime_minutes'] . ' mnt' : '-' }}
                        </td>
                        <td style="text-align: center;">{{ ($log['working_hours'] ?? 0) > 0 ? number_format($log['working_hours'], 1) . ' jam' : '-' }}</td>
                        <td style="text-align: center;">
                            <span class="badge-status {{ $classStatus }}">{{ $statusLabel }}</span>
                        </td>
                        <td style="color: #64748b;">{{ $log['notes'] ?: '-' }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 12px; color: #94a3b8;">
                            Tidak ada rincian log kehadiran untuk periode ini.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Footer Halaman 2 -->
    <div class="confidential" style="margin-top: 25px;">
        * Halaman 2: Lampiran Rekapitulasi & Rincian Log Absensi Karyawan.<br>
        * Dicetak secara otomatis dari HRMS pada {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB.
    </div>

</body>
</html>

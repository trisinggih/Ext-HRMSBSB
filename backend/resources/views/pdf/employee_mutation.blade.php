<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keputusan {{ ucfirst($mutation->type) }} - {{ $mutation->memo_number }}</title>
    <style>
        @page {
            margin: 20px 30px 20px 30px;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        /* KOP SURAT */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-logo {
            width: 80px;
            text-align: left;
            padding-right: 12px;
        }
        .kop-logo img {
            max-height: 52px;
            max-width: 80px;
            display: block;
        }
        .kop-badge {
            background-color: #008779;
            color: #ffffff;
            font-weight: bold;
            font-size: 13pt;
            padding: 10px 8px;
            text-align: center;
            border-radius: 4px;
            letter-spacing: 1px;
        }
        .kop-text {
            text-align: center;
        }
        .kop-company {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .kop-branch {
            font-size: 10.5pt;
            font-weight: bold;
            color: #008779;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }
        .kop-address {
            font-size: 8pt;
            color: #475569;
            margin: 0 0 2px 0;
            line-height: 1.3;
        }
        .kop-contact {
            font-size: 8pt;
            color: #475569;
            margin: 0;
        }
        .double-divider {
            border-top: 2px solid #008779;
            border-bottom: 0.75px solid #0f172a;
            height: 2px;
            margin-top: 6px;
            margin-bottom: 12px;
        }

        /* TITLE */
        .title-block {
            text-align: center;
            margin-bottom: 14px;
        }
        .title-main {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
            margin: 0 0 2px 0;
        }
        .title-sub {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #008779;
            margin: 0 0 3px 0;
        }
        .title-number {
            font-size: 9pt;
            color: #334155;
            font-family: 'Courier', monospace;
            font-weight: bold;
            margin: 0;
        }

        /* CONTENT */
        p.intro-text {
            margin: 0 0 8px 0;
            text-align: justify;
            font-size: 9pt;
            color: #334155;
        }

        /* EMPLOYEE BOX */
        .emp-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .emp-box td {
            padding: 4px 8px;
            font-size: 9pt;
            vertical-align: top;
        }
        .emp-box td.label {
            width: 22%;
            font-weight: bold;
            color: #475569;
        }
        .emp-box td.sep {
            width: 2%;
            text-align: center;
        }
        .emp-box td.val {
            width: 76%;
            font-weight: bold;
            color: #0f172a;
        }

        /* SECTION HEADER */
        .sec-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #008779;
            margin: 8px 0 4px 0;
            letter-spacing: 0.5px;
        }

        /* COMPARISON TABLE */
        .table-compare {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8.5pt;
        }
        .table-compare th, .table-compare td {
            border: 1px solid #cbd5e1;
            padding: 4.5px 6px;
            vertical-align: middle;
        }
        .table-compare th {
            background-color: #008779;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.5px;
        }
        .table-compare th.col-aspek {
            width: 26%;
            background-color: #006b60;
        }
        .table-compare th.col-old {
            width: 37%;
            background-color: #475569;
        }
        .table-compare th.col-new {
            width: 37%;
            background-color: #008779;
        }
        .table-compare td.aspek {
            font-weight: bold;
            background-color: #f8fafc;
            color: #334155;
        }
        .table-compare td.old-val {
            color: #475569;
            background-color: #ffffff;
        }
        .table-compare td.new-val {
            font-weight: bold;
            color: #006b60;
            background-color: #f0fdfa;
        }

        /* NOTES */
        .notes-box {
            border-left: 3px solid #008779;
            background-color: #f8fafc;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 8.5pt;
        }
        .notes-box b {
            color: #008779;
        }

        /* SIGNATURES */
        .sig-header {
            text-align: right;
            font-size: 8.5pt;
            margin-bottom: 8px;
            color: #334155;
        }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        .sig-table td {
            vertical-align: top;
            text-align: center;
            padding: 0 4px;
        }
        .sig-role {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 4px;
            min-height: 28px;
        }
        .sig-space {
            height: 54px;
            position: relative;
            text-align: center;
            vertical-align: middle;
        }
        .sig-img {
            max-height: 48px;
            max-width: 110px;
            display: inline-block;
            vertical-align: middle;
        }
        .sig-badge-approved {
            display: inline-block;
            border: 1px solid #16a34a;
            color: #16a34a;
            background-color: #f0fdf4;
            font-size: 7pt;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            margin-top: 10px;
        }
        .sig-badge-pending {
            display: inline-block;
            border: 1px dashed #94a3b8;
            color: #64748b;
            background-color: #f8fafc;
            font-size: 7pt;
            padding: 2px 6px;
            border-radius: 3px;
            margin-top: 10px;
        }
        .sig-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #0f172a;
            padding-bottom: 2px;
            display: inline-block;
            min-width: 110px;
        }
        .sig-position {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 2px;
        }
        .sig-nip {
            font-size: 7pt;
            color: #94a3b8;
            font-family: 'Courier', monospace;
        }

        /* FOOTER / TEMBUSAN */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            font-size: 7.5pt;
            color: #64748b;
        }
        .footer-table td {
            vertical-align: top;
        }
        .tembusan-title {
            font-weight: bold;
            color: #475569;
            margin-bottom: 2px;
        }
        .tembusan-list {
            margin: 0;
            padding-left: 14px;
            line-height: 1.3;
        }
        .system-stamp {
            text-align: right;
            font-style: italic;
            color: #94a3b8;
        }
    </style>
</head>
<body>

@php
    // Resolusi Asal Cabang dan Perusahaan
    $originBranch = $mutation->oldBranch 
        ?? $mutation->employee?->primaryAssignment?->branch 
        ?? $mutation->creator?->primaryAssignment?->branch;

    $originCompany = $mutation->oldCompany 
        ?? $mutation->employee?->primaryAssignment?->company 
        ?? $mutation->creator?->primaryAssignment?->company 
        ?? \App\Models\Company::first();

    // Resolusi Logo
    $logoBase64 = null;
    if ($originBranch && method_exists($originBranch, 'getEffectiveLogoBase64Attribute')) {
        $logoBase64 = $originBranch->effective_logo_base64;
    } elseif ($originCompany && method_exists($originCompany, 'getEffectiveLogoBase64Attribute')) {
        $logoBase64 = $originCompany->effective_logo_base64;
    }
    if (!$logoBase64) {
        $group = \App\Models\CompanyGroup::first();
        if ($group && $group->logo && file_exists(storage_path('app/public/' . $group->logo))) {
            $p = storage_path('app/public/' . $group->logo);
            $mime = mime_content_type($p) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
        }
    }

    $companyName = $originCompany?->legal_name ?: ($originCompany?->name ?? 'JANGEBY GROUP');
    $branchName = $originBranch?->name ? 'CABANG ' . strtoupper($originBranch->name) : 'KANTOR PUSAT';
    $displayAddress = $originBranch?->address ?: ($originCompany?->address ?? 'Kota Depok, Jawa Barat');
    $displayCity = $originBranch?->city ?: ($originCompany?->city ?? 'Depok');
    $displayPhone = $originBranch?->phone ?: ($originCompany?->phone ?? '-');
    $displayEmail = $originBranch?->email ?: ($originCompany?->email ?? '-');

    $isPromosi = strtolower($mutation->type) === 'promosi';
    $isDemosi = strtolower($mutation->type) === 'demosi';
    if ($isPromosi) {
        $titleJenis = 'PROMOSI JABATAN KARYAWAN';
        $jenisText = 'promosi jabatan';
    } elseif ($isDemosi) {
        $titleJenis = 'DEMOSI JABATAN / PENURUNAN LEVEL';
        $jenisText = 'demosi jabatan / penyesuaian level';
    } else {
        $titleJenis = 'MUTASI / ROTASI PENEMPATAN KARYAWAN';
        $jenisText = 'mutasi penempatan';
    }
    $effectiveDateFormatted = \Carbon\Carbon::parse($mutation->effective_date)->translatedFormat('d F Y');
    $createdDateFormatted = \Carbon\Carbon::parse($mutation->created_at)->translatedFormat('d F Y');

    // Kolom Tanda Tangan:
    // Kolom 1: Dibuat Oleh (HR / Creator)
    // Kolom 2..N: Disetujui Oleh (Approvers dari Workflow)
    // Kolom Terakhir: Karyawan Bersangkutan
    $creator = $mutation->creator;
    $creatorName = $creator?->name ?? 'HR & GA Department';
    $creatorPosition = $creator?->primaryAssignment?->position?->name ?? 'HR & GA Officer';
    $creatorNip = $creator?->employee_id ?? ($creator?->nip ?? '-');

    $totalCols = 2 + (count($approverSteps) > 0 ? count($approverSteps) : 1);
    $colWidth = round(100 / $totalCols, 1) . '%';
@endphp

    <!-- 1. KOP SURAT BERDASARKAN CABANG / PERUSAHAAN ASAL -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" />
                @else
                    <div class="kop-badge">{{ substr($companyName, 0, 3) }}</div>
                @endif
            </td>
            <td class="kop-text">
                <div class="kop-company">{{ $companyName }}</div>
                <div class="kop-branch">{{ $branchName }}</div>
                <div class="kop-address">{{ $displayAddress }}</div>
                <div class="kop-contact">
                    @if($displayPhone && $displayPhone !== '-')Telp: {{ $displayPhone }} &bull; @endif
                    @if($displayEmail && $displayEmail !== '-')Email: {{ $displayEmail }}@endif
                </div>
            </td>
        </tr>
    </table>

    <div class="double-divider"></div>

    <!-- 2. JUDUL SURAT KEPUTUSAN -->
    <div class="title-block">
        <div class="title-main">SURAT KEPUTUSAN DIREKSI / MANAJEMEN</div>
        <div class="title-sub">TENTANG {{ $titleJenis }}</div>
        <div class="title-number">Nomor: {{ $mutation->memo_number }}</div>
    </div>

    <!-- 3. PEMBUKA -->
    <p class="intro-text">
        Menimbang kebutuhan organisasi, optimalisasi kinerja operasional perusahaan, serta penataan formasi sumber daya manusia yang berkelanjutan, maka Manajemen dengan ini memutuskan dan menetapkan keputusan {{ $jenisText }} kepada karyawan berikut:
    </p>

    <!-- 4. DATA KARYAWAN BERSANGKUTAN -->
    <table class="emp-box">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="sep">:</td>
            <td class="val">{{ $mutation->employee->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIP / ID Karyawan</td>
            <td class="sep">:</td>
            <td class="val">{{ $mutation->employee->employee_id ?: ($mutation->employee->nip ?: '-') }}</td>
        </tr>
        <tr>
            <td class="label">Status Kepegawaian</td>
            <td class="sep">:</td>
            <td class="val">{{ ucwords($mutation->employee->primaryAssignment?->employment_status ?? 'Karyawan Tetap') }}</td>
        </tr>
    </table>

    <!-- 5. TABEL PERBANDINGAN FORMASI (ASAL VS TARGET BARU) -->
    <div class="sec-title">FORMASI PERUBAHAN PENEMPATAN KERJA</div>
    <table class="table-compare">
        <thead>
            <tr>
                <th class="col-aspek">Aspek Penempatan</th>
                <th class="col-old">Formasi Asal (Lama)</th>
                <th class="col-new">Formasi Target (Baru)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="aspek">Perusahaan</td>
                <td class="old-val">{{ $mutation->oldCompany->name ?? '-' }}</td>
                <td class="new-val">{{ $mutation->newCompany->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="aspek">Cabang / Unit Kerja</td>
                <td class="old-val">{{ $mutation->oldBranch->name ?? '-' }}</td>
                <td class="new-val">{{ $mutation->newBranch->name ?? ($mutation->oldBranch->name ?? '-') }}</td>
            </tr>
            <tr>
                <td class="aspek">Departemen</td>
                <td class="old-val">{{ $mutation->oldDepartment->name ?? '-' }}</td>
                <td class="new-val">{{ $mutation->newDepartment->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="aspek">Divisi</td>
                <td class="old-val">{{ $mutation->oldDivision->name ?? '-' }}</td>
                <td class="new-val">{{ $mutation->newDivision->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="aspek">Jabatan Fungsional</td>
                <td class="old-val">{{ $mutation->oldPosition->name ?? '-' }}</td>
                <td class="new-val">{{ $mutation->newPosition->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="aspek">Atasan Langsung</td>
                <td class="old-val">{{ $mutation->oldSupervisor->name ?? '-' }}</td>
                <td class="new-val">{{ $mutation->newSupervisor->name ?? '-' }}</td>
            </tr>
            @if($mutation->new_nip && $mutation->new_nip !== ($mutation->employee->employee_id ?: $mutation->employee->nip))
            <tr>
                <td class="aspek">NIP / ID Karyawan Baru</td>
                <td class="old-val">{{ $mutation->employee->employee_id ?: ($mutation->employee->nip ?: '-') }}</td>
                <td class="new-val" style="font-weight: bold; color: #008779;">{{ $mutation->new_nip }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- 6. KETENTUAN PEMBERLAKUAN & CATATAN -->
    <div class="notes-box">
        <div><b>Tanggal Efektif Berlaku:</b> {{ $effectiveDateFormatted }}</div>
        <div style="margin-top: 3px;">
            <b>Catatan / Pertimbangan:</b> 
            {{ $mutation->reason ?: 'Surat Keputusan ini dikeluarkan untuk mendukung efektivitas formasi operasional dan pencapaian target organisasi perusahaan.' }}
        </div>
        <div style="margin-top: 3px; font-size: 8pt; color: #475569;">
            Segala hak, wewenang, dan tanggung jawab pada posisi baru mulai berlaku penuh sejak tanggal efektif. Karyawan diwajibkan menyelesaikan proses serah terima tugas (<i>handover</i>) dengan unit kerja sebelumnya.
        </div>
    </div>

    <!-- 7. TANDA TANGAN (DIAJUKAN, APPROVERS RESMI, & KARYAWAN) -->
    <div class="sig-header">
        Ditetapkan di: {{ $displayCity }}, {{ $createdDateFormatted }}
    </div>

    <table class="sig-table">
        <tr>
            <!-- KOLOM 1: DIBUAT OLEH (HR / CREATOR) -->
            <td style="width: {{ $colWidth }};">
                <div class="sig-role">Dibuat Oleh,<br><span style="font-weight: normal; font-size: 7.5pt; color: #64748b;">(HR & Personalia)</span></div>
                <div class="sig-space">
                    @if(!empty($creatorSignature))
                        <img src="{{ $creatorSignature }}" class="sig-img" alt="TTD HR" />
                        <div style="font-size: 6pt; color: #16a34a; font-weight: bold; margin-top: 1px;">TERVERIFIKASI SISTEM</div>
                    @else
                        <span class="sig-badge-approved">TERVERIFIKASI SISTEM</span>
                    @endif
                </div>
                <div class="sig-name">{{ $creatorName }}</div>
                <div class="sig-position">{{ $creatorPosition }}</div>
                <div class="sig-nip">NIP. {{ $creatorNip }}</div>
            </td>

            <!-- KOLOM 2..N: APPROVER SESUAI WORKFLOW PERSETUJUAN -->
            @if(count($approverSteps) > 0)
                @foreach($approverSteps as $step)
                    <td style="width: {{ $colWidth }};">
                        <div class="sig-role">
                            Disetujui Oleh,<br>
                            <span style="font-weight: normal; font-size: 7.5pt; color: #64748b;">
                                (Approver Tk. {{ $step['order'] }})
                            </span>
                        </div>
                        <div class="sig-space">
                            @if($step['is_approved'])
                                @if(!empty($step['signature_base64']))
                                    <img src="{{ $step['signature_base64'] }}" class="sig-img" alt="TTD Approver" />
                                    <div style="font-size: 6pt; color: #16a34a; font-weight: bold; margin-top: 1px;">DISETUJUI DIGITAL {{ $step['approved_at'] }}</div>
                                @else
                                    <span class="sig-badge-approved">DISETUJUI DIGITAL<br>{{ $step['approved_at'] }}</span>
                                @endif
                            @else
                                <span class="sig-badge-pending">MENUNGGU TTD</span>
                            @endif
                        </div>
                        <div class="sig-name">{{ $step['name'] }}</div>
                        <div class="sig-position">{{ $step['position'] }}</div>
                        <div class="sig-nip">NIP. {{ $step['nip'] }}</div>
                    </td>
                @endforeach
            @else
                <td style="width: {{ $colWidth }};">
                    <div class="sig-role">Disetujui Oleh,<br><span style="font-weight: normal; font-size: 7.5pt; color: #64748b;">(Manajemen)</span></div>
                    <div class="sig-space">
                        @if(!empty($mutation->oldSupervisor?->signature_base64))
                            <img src="{{ $mutation->oldSupervisor->signature_base64 }}" class="sig-img" alt="TTD Atasan" />
                            <div style="font-size: 6pt; color: #16a34a; font-weight: bold; margin-top: 1px;">TERVERIFIKASI</div>
                        @else
                            <span class="sig-badge-pending">MENUNGGU TTD</span>
                        @endif
                    </div>
                    <div class="sig-name">{{ $mutation->oldSupervisor->name ?? 'Pimpinan Unit Kerja' }}</div>
                    <div class="sig-position">Atasan Langsung / Manajemen</div>
                    <div class="sig-nip">-</div>
                </td>
            @endif

            <!-- KOLOM TERAKHIR: KARYAWAN BERSANGKUTAN -->
            <td style="width: {{ $colWidth }};">
                <div class="sig-role">Diterima & Dijalankan,<br><span style="font-weight: normal; font-size: 7.5pt; color: #64748b;">(Karyawan Bersangkutan)</span></div>
                <div class="sig-space">
                    @if(!empty($employeeSignature))
                        <img src="{{ $employeeSignature }}" class="sig-img" alt="TTD Karyawan" />
                    @else
                        <span class="sig-badge-pending">TANDA TANGAN</span>
                    @endif
                </div>
                <div class="sig-name">{{ $mutation->employee->name ?? 'Karyawan' }}</div>
                <div class="sig-position">Karyawan Terkait</div>
                <div class="sig-nip">NIP. {{ $mutation->employee->employee_id ?: ($mutation->employee->nip ?: '-') }}</div>
            </td>
        </tr>
    </table>

    <!-- 8. TEMBUSAN & WATERMARK FOOTER -->
    <table class="footer-table">
        <tr>
            <td style="width: 60%;">
                <div class="tembusan-title">Tembusan disampaikan kepada Yth:</div>
                <ol class="tembusan-list">
                    <li>Direksi / Manajemen {{ $companyName }}</li>
                    <li>Head of Human Resource & General Affairs (HR & GA)</li>
                    <li>Pimpinan Unit Kerja Asal & Unit Kerja Baru</li>
                    <li>Arsip Pribadi Karyawan (Personal File)</li>
                </ol>
            </td>
            <td style="width: 40%; text-align: right;" class="system-stamp">
                <div>Dicetak otomatis melalui HRMS Jangeby Group</div>
                <div>Waktu cetak: {{ now()->translatedFormat('d F Y H:i:s') }} WIB</div>
                <div style="font-size: 6.5pt; color: #cbd5e1; margin-top: 3px;">Document Integrity: Validated by HRMS Automation Engine</div>
            </td>
        </tr>
    </table>

</body>
</html>

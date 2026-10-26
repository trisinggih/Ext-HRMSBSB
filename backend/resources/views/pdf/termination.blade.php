<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Terminasi - {{ $termination->employee->name ?? 'Karyawan' }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 25px;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1a252f;
        }
        .header p {
            margin: 4px 0 0;
            font-size: 9.5pt;
            color: #555;
        }
        .title {
            text-align: center;
            margin-bottom: 25px;
        }
        .title h2 {
            margin: 0;
            font-size: 13pt;
            text-decoration: underline;
            text-transform: uppercase;
            color: #111;
        }
        .title p {
            margin: 4px 0 0;
            font-size: 10pt;
            color: #666;
        }
        .content {
            margin-bottom: 30px;
        }
        .table-info {
            width: 100%;
            margin: 15px 0 20px 0;
            border-collapse: collapse;
        }
        .table-info td {
            padding: 6px 4px;
            vertical-align: top;
        }
        .table-info td.label {
            width: 170px;
            font-weight: bold;
            color: #2c3e50;
        }
        .table-info td.sep {
            width: 15px;
            text-align: center;
        }
        .badge-type {
            display: inline-block;
            padding: 3px 8px;
            background-color: #eef2f7;
            color: #2c3e50;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10pt;
            text-transform: uppercase;
        }
        .footer-signature {
            margin-top: 60px;
            width: 100%;
        }
        .signature-box {
            width: 45%;
            float: left;
            text-align: center;
        }
        .signature-box.right {
            float: right;
        }
        .signature-box .title-role {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .signature-box .sig-space {
            height: 60px;
            text-align: center;
            vertical-align: middle;
            margin-bottom: 5px;
        }
        .signature-box .name {
            font-weight: bold;
            text-decoration: underline;
        }
        .clear {
            clear: both;
        }
        .text-justify {
            text-align: justify;
        }
        .note-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 10px 15px;
            margin: 20px 0;
            font-size: 10pt;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ $company->name ?? 'PT. HRMS SYSTEM' }}</h1>
        <p>{{ $company->address ?? 'Gedung Perkantoran, Jakarta, Indonesia' }}<br>
        Telp: {{ $company->phone ?? '-' }} | Email: {{ $company->email ?? 'hrd@company.com' }}</p>
    </div>

    <div class="title">
        <h2>SURAT KETERANGAN TERMINASI / PEMBERHENTIAN</h2>
        <p>Nomor: TERM/{{ date('Y/m') }}/{{ str_pad($termination->id, 4, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="content text-justify">
        <p>Yang bertanda tangan di bawah ini atas nama Manajemen <strong>{{ $company->name ?? 'Perusahaan' }}</strong>, menerangkan bahwa karyawan dengan identitas sebagai berikut:</p>

        <table class="table-info">
            <tr>
                <td class="label">Nama Lengkap</td>
                <td class="sep">:</td>
                <td><strong>{{ $termination->employee->name ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td class="label">NIP / ID Karyawan</td>
                <td class="sep">:</td>
                <td>{{ $termination->employee->employee_id ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Departemen</td>
                <td class="sep">:</td>
                <td>{{ $termination->employee->primaryAssignment->department->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td>
                <td class="sep">:</td>
                <td>{{ $termination->employee->primaryAssignment->position->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Tipe Terminasi</td>
                <td class="sep">:</td>
                <td>
                    <span class="badge-type">
                        @if($termination->type === 'resignation') Pengunduran Diri (Resign)
                        @elseif($termination->type === 'phk') Pemutusan Hubungan Kerja (PHK)
                        @elseif($termination->type === 'contract_end') Habis Masa Kontrak
                        @elseif($termination->type === 'retirement') Pensiun
                        @elseif($termination->type === 'termination') Pemberhentian Kerja
                        @else {{ strtoupper(str_replace('_', ' ', $termination->type)) }}
                        @endif
                    </span>
                </td>
            </tr>
            <tr>
                <td class="label">Tanggal Efektif</td>
                <td class="sep">:</td>
                <td><strong>{{ $termination->termination_date ? \Carbon\Carbon::parse($termination->termination_date)->isoFormat('D MMMM YYYY') : '-' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Status Pengajuan</td>
                <td class="sep">:</td>
                <td>{{ strtoupper($termination->status) }}</td>
            </tr>
        </table>

        @if($termination->reason)
        <div class="note-box">
            <strong>Alasan / Keterangan:</strong><br>
            {{ $termination->reason }}
        </div>
        @endif

        <p>Dengan diterbitkannya surat ini, maka segala kewajiban dan hak ketenagakerjaan antara pihak Perusahaan dan Karyawan bersangkutan diselesaikan sesuai dengan ketentuan peraturan perundang-undangan dan perjanjian kerja yang berlaku.</p>

        <p>Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
    </div>

    <div class="footer-signature">
        <div class="signature-box">
            <p>Diterima Oleh,</p>
            <p class="title-role">Karyawan Bersangkutan</p>
            <div class="sig-space">
                @if(!empty($termination->employee?->signature_base64))
                    <img src="{{ $termination->employee->signature_base64 }}" style="max-height: 55px; max-width: 130px; display: inline-block;" alt="TTD Karyawan" />
                @endif
            </div>
            <p class="name">{{ $termination->employee->name ?? 'Karyawan' }}</p>
        </div>

        <div class="signature-box right">
            <p>{{ $company->city ?? 'Jakarta' }}, {{ \Carbon\Carbon::parse($termination->termination_date ?? now())->translatedFormat('d F Y') }}</p>
            <p class="title-role">HRD Manager / Manajemen</p>
            <div class="sig-space"></div>
            <p class="name">( Head of Human Resources )</p>
        </div>
        <div class="clear"></div>
    </div>

</body>
</html>

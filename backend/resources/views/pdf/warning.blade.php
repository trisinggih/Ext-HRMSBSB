<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Peringatan - {{ $warning->employee->name ?? 'Karyawan' }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20pt;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 10pt;
            color: #666;
        }
        .title {
            text-align: center;
            margin-bottom: 30px;
        }
        .title h2 {
            margin: 0;
            font-size: 14pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .title p {
            margin: 5px 0 0;
            font-size: 11pt;
        }
        .content {
            margin-bottom: 30px;
        }
        .table-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .table-info td {
            padding: 5px;
            vertical-align: top;
        }
        .table-info td:first-child {
            width: 150px;
            font-weight: bold;
        }
        .table-info td:nth-child(2) {
            width: 10px;
        }
        .footer {
            margin-top: 50px;
            width: 100%;
        }
        .signature {
            width: 50%;
            float: left;
            text-align: center;
        }
        .signature p {
            margin: 0;
        }
        .signature .name {
            margin-top: 80px;
            font-weight: bold;
            text-decoration: underline;
        }
        .clear {
            clear: both;
        }
        .text-justify {
            text-align: justify;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>PT. BURSATAV NUSANTARA</h1>
        <p>Gedung Perkantoran Sudirman, Jl. Jend. Sudirman Kav 21, Jakarta Selatan 12920<br>
        Telp: (021) 1234567 | Email: hrd@bursatav.co.id | Web: www.bursatav.co.id</p>
    </div>

    <div class="title">
        <h2>
            @if(strtolower($warning->warning_type) == 'sp1')
                SURAT PERINGATAN PERTAMA (SP-1)
            @elseif(strtolower($warning->warning_type) == 'sp2')
                SURAT PERINGATAN KEDUA (SP-2)
            @elseif(strtolower($warning->warning_type) == 'sp3')
                SURAT PERINGATAN KETIGA (SP-3)
            @else
                SURAT TEGURAN
            @endif
        </h2>
        <p>Nomor: HRD/SP/{{ date('Y/m', strtotime($warning->issue_date)) }}/{{ str_pad($warning->id, 3, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="content">
        <p>Surat ini diberikan kepada:</p>
        <table class="table-info">
            <tr>
                <td>Nama</td>
                <td>:</td>
                <td>{{ $warning->employee->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>ID Karyawan</td>
                <td>:</td>
                <td>{{ $warning->employee->employee_id ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tanggal Dikeluarkan</td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::parse($warning->issue_date)->translatedFormat('d F Y') }}</td>
            </tr>
            @if($warning->expired_date)
            <tr>
                <td>Masa Berlaku</td>
                <td>:</td>
                <td>Hingga {{ \Carbon\Carbon::parse($warning->expired_date)->translatedFormat('d F Y') }}</td>
            </tr>
            @endif
        </table>

        <p class="text-justify">
            Sehubungan dengan pelanggaran tata tertib perusahaan yang dilakukan oleh Saudara/i, maka pihak manajemen memutuskan untuk mengeluarkan Surat Peringatan ini.
        </p>
        
        <p class="text-justify">
            <strong>Adapun alasan atau bentuk pelanggaran adalah sebagai berikut:</strong><br>
            {{ $warning->reason }}
        </p>

        <p class="text-justify">
            Surat peringatan ini dikeluarkan agar Saudara/i dapat memperbaiki kinerja serta kedisiplinan, dan tidak mengulangi kesalahan yang sama di kemudian hari. Apabila selama masa berlakunya surat peringatan ini Saudara/i kembali melakukan pelanggaran, maka perusahaan akan mengambil tindakan tegas sesuai dengan peraturan yang berlaku, hingga pada tahap Pemutusan Hubungan Kerja (PHK).
        </p>
        
        <p class="text-justify">
            Demikian surat peringatan ini dibuat untuk diketahui dan dilaksanakan sebagaimana mestinya.
        </p>
    </div>

    <div class="footer">
        <div class="signature">
            <p>Mengetahui/Menyetujui,</p>
            <p><strong>Pihak Perusahaan</strong></p>
            <div style="height: 60px; margin-top: 10px;"></div>
            <div class="name" style="margin-top: 0;">HR Manager</div>
        </div>
        <div class="signature">
            <p>{{ $warning->branch->city ?? 'Jakarta' }}, {{ \Carbon\Carbon::parse($warning->issue_date)->translatedFormat('d F Y') }}</p>
            <p><strong>Karyawan Yang Bersangkutan</strong></p>
            <div style="height: 60px; margin-top: 10px; text-align: center;">
                @if(!empty($warning->employee?->signature_base64))
                    <img src="{{ $warning->employee->signature_base64 }}" style="max-height: 55px; max-width: 130px; display: inline-block;" alt="TTD Karyawan" />
                @endif
            </div>
            <div class="name" style="margin-top: 0;">{{ $warning->employee->name ?? '.......................' }}</div>
        </div>
        <div class="clear"></div>
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Permohonan Perbaikan Alat Kerja - {{ $repair->id }}</title>
    <style>
        @page {
            margin: 25px 35px 20px 35px;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5pt;
            color: #000;
            line-height: 1.35;
            margin: 0;
            padding: 5px 10px;
        }

        .title-wrapper {
            text-align: center;
            margin-bottom: 18px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }

        .lead-text {
            font-size: 9.5pt;
            margin-bottom: 8px;
        }

        /* Bio 2-column table */
        .bio-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9.5pt;
        }
        .bio-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .bio-label-left {
            width: 110px;
        }
        .bio-sep {
            width: 15px;
            text-align: center;
        }
        .bio-val-left {
            width: 210px;
            border-bottom: 1px dotted #888;
        }
        .bio-label-right {
            width: 130px;
            padding-left: 15px !important;
        }
        .bio-val-right {
            border-bottom: 1px dotted #888;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        /* Grid Table for Section A & C */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .grid-table td {
            border: 1.2px solid #000;
            padding: 5px 8px;
            vertical-align: middle;
        }

        .checkbox-box {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1.2px solid #000;
            text-align: center;
            line-height: 10px;
            font-size: 8pt;
            font-weight: bold;
            vertical-align: middle;
            margin-right: 4px;
        }

        /* Section B Box Table */
        .box-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .box-table td {
            border: 1.2px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }
        .box-row-content {
            min-height: 48px;
            font-size: 9pt;
            line-height: 1.4;
            color: #111;
        }
        .box-label {
            font-weight: normal;
            margin-bottom: 4px;
        }

        .dotted-line {
            border-bottom: 1px dotted #999;
            display: inline-block;
            width: 100%;
            min-height: 14px;
        }

        /* Signatures Area */
        .signatures-area {
            margin-top: 15px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures-area td {
            vertical-align: top;
            text-align: center;
            font-size: 9pt;
        }
        .sig-header {
            font-weight: bold;
            padding-bottom: 6px;
        }
        .sig-space {
            height: 52px;
            text-align: center;
            vertical-align: middle;
            margin-bottom: 4px;
        }
        .sig-img {
            max-height: 48px;
            max-width: 110px;
            display: inline-block;
            vertical-align: middle;
        }
        .sig-name {
            font-weight: 500;
            margin-bottom: 4px;
        }
        .sig-role {
            font-size: 8.5pt;
            color: #222;
        }
    </style>
</head>
<body>

    <!-- JUDUL DOKUMEN -->
    <div class="title-wrapper">
        <h3 class="doc-title">FORMULIR PERMOHONAN PERBAIKAN ALAT KERJA</h3>
    </div>

    <!-- IDENTITAS PEMOHON -->
    <div class="lead-text">Saya yang bertanda tangan di bawah ini:</div>

    <table class="bio-table">
        <tr>
            <td class="bio-label-left">Nama Karyawan</td>
            <td class="bio-sep">:</td>
            <td class="bio-val-left"><strong>{{ $requesterName }}</strong></td>
            <td class="bio-label-right">NIP</td>
            <td class="bio-sep">:</td>
            <td class="bio-val-right">{{ $nip }}</td>
        </tr>
        <tr>
            <td class="bio-label-left">Jabatan</td>
            <td class="bio-sep">:</td>
            <td class="bio-val-left">{{ $jabatan }}</td>
            <td class="bio-label-right">Departemen / Divisi</td>
            <td class="bio-sep">:</td>
            <td class="bio-val-right">{{ $departemen }} {{ $divisi && $divisi !== '-' ? '/ ' . $divisi : '' }}</td>
        </tr>
        <tr>
            <td class="bio-label-left">Cabang</td>
            <td class="bio-sep">:</td>
            <td class="bio-val-left">{{ $cabang }}</td>
            <td class="bio-label-right">Tanggal/Bulan Pengajuan</td>
            <td class="bio-sep">:</td>
            <td class="bio-val-right">{{ $submissionInfo }}</td>
        </tr>
    </table>

    <div class="lead-text" style="margin-bottom: 8px;">
        Dengan ini mengajukan permohonan perbaikan alat kerja yang digunakan untuk menunjang pelaksanaan pekerjaan, dengan rincian sebagai berikut:
    </div>

    <!-- A. DETAIL ALAT KERJA -->
    <div class="section-title">A. Detail Alat Kerja</div>
    <table class="grid-table">
        <tr>
            <td style="width: 50%;">
                <strong>Nama Alat</strong> : {{ $repair->item_name }}
            </td>
            <td style="width: 50%;">
                <strong>Merek / Tipe</strong> : {{ $repair->brand_model ?: '-' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Kode Aset (jika ada)</strong> : {{ $repair->asset_code ?: ($repair->asset ? $repair->asset->inventory_number : '-') }}
            </td>
            <td>
                <strong>Jumlah</strong> : {{ $repair->quantity ?: 1 }} unit
            </td>
        </tr>
        <tr>
            <td>
                <strong>Lokasi Alat</strong> : {{ $repair->location ?: '-' }}
            </td>
            <td>
                <strong>Kondisi Alat</strong> : 
                <span class="checkbox-box">{{ $repair->condition === 'rusak' ? 'X' : '' }}</span> Rusak &nbsp;&nbsp;
                <span class="checkbox-box">{{ $repair->condition === 'tidak_berfungsi' ? 'X' : '' }}</span> Tidak berfungsi &nbsp;&nbsp;
                <span class="checkbox-box">{{ ($repair->condition === 'lainnya' || $repair->condition_other) ? 'X' : '' }}</span> Lainnya
            </td>
        </tr>
    </table>

    <!-- B. URAIAN KERUSAKAN DAN PERMOHONAN -->
    <div class="section-title">B. Uraian Kerusakan dan Permohonan</div>
    <table class="box-table">
        <tr>
            <td colspan="2">
                <div class="box-label">Uraian kerusakan / kendala:</div>
                <div class="box-row-content">
                    {{ $repair->damage_description }}
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="box-label">Perbaikan yang dibutuhkan / keterangan tambahan:</div>
                <div class="box-row-content">
                    {{ $repair->repair_description ?: '-' }}
                </div>
            </td>
        </tr>
        <tr>
            <td style="width: 50%;">
                <strong>PIC / pengguna alat</strong>: {{ $repair->pic_name ?: $requesterName }}
            </td>
            <td style="width: 50%;">
                <span class="checkbox-box">{{ $repair->operational_disrupted ? 'X' : '' }}</span> Operasional terganggu &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="checkbox-box">{{ !$repair->operational_disrupted ? 'X' : '' }}</span> Tidak
            </td>
        </tr>
    </table>

    <!-- C. ESTIMASI DAN TINDAK LANJUT -->
    <div class="section-title">C. Estimasi dan Tindak Lanjut</div>
    <table class="grid-table">
        <tr>
            <td style="width: 50%;">
                <strong>Estimasi biaya (jika ada)</strong> : Rp {{ $repair->estimated_cost ? number_format($repair->estimated_cost, 0, ',', '.') : '........................' }}
            </td>
            <td style="width: 50%;">
                <strong>Prioritas</strong> : 
                <span class="checkbox-box">{{ $repair->priority === 'tinggi' ? 'X' : '' }}</span> Tinggi &nbsp;&nbsp;
                <span class="checkbox-box">{{ $repair->priority === 'sedang' ? 'X' : '' }}</span> Sedang &nbsp;&nbsp;
                <span class="checkbox-box">{{ $repair->priority === 'rendah' ? 'X' : '' }}</span> Rendah
            </td>
        </tr>
        <tr>
            <td>
                <strong>Nama Bank & No Rekening</strong> : {{ $repair->bank_account_info ?: '-' }}
            </td>
            <td>
                <strong>Tanggal perbaikan</strong> : {{ $repair->repair_date ? \Carbon\Carbon::parse($repair->repair_date)->translatedFormat('d F Y') : '-' }}
            </td>
        </tr>
    </table>

    <div class="lead-text" style="margin-top: 10px; margin-bottom: 12px;">
        Demikian permohonan perbaikan alat kerja ini saya ajukan. Atas perhatian dan persetujuannya, saya ucapkan terima kasih.
    </div>

    <!-- TANGGAL PENGAJUAN -->
    <div style="text-align: right; margin-bottom: 12px; font-size: 9pt;">
        {{ $cityName }}, {{ $dateFormatted }}
    </div>

    <!-- TANDA TANGAN (4 PIHAK SESUAI GAMBAR) -->
    <table class="signatures-area">
        <tr>
            <td style="width: 25%;" class="sig-header">Yang Mengajukan</td>
            <td style="width: 25%;" class="sig-header">Mengetahui,</td>
            <td style="width: 25%;" class="sig-header">Mengetahui,</td>
            <td style="width: 25%;" class="sig-header">Menyetujui,</td>
        </tr>
        <tr>
            <td>
                <div class="sig-space">
                    @if(!empty($requesterSignature))
                        <img src="{{ $requesterSignature }}" class="sig-img" alt="TTD Pemohon" />
                    @else
                        <div style="height: 48px;"></div>
                    @endif
                </div>
                <div class="sig-name">( {{ $requesterName }} )</div>
                <div class="sig-role">{{ $jabatan ?: 'Manager Operasional' }}</div>
            </td>
            <td>
                <div class="sig-space">
                    @if(!empty($hrManagerSignature))
                        <img src="{{ $hrManagerSignature }}" class="sig-img" alt="TTD HR" />
                    @else
                        <div style="height: 48px;"></div>
                    @endif
                </div>
                <div class="sig-name">{{ $hrManagerName ?: 'Agus Prasetyo' }}</div>
                <div class="sig-role">{{ $hrManagerTitle ?: 'Manager HR & GA' }}</div>
            </td>
            <td>
                <div class="sig-space">
                    @if(!empty($financeManagerSignature))
                        <img src="{{ $financeManagerSignature }}" class="sig-img" alt="TTD Finance" />
                    @else
                        <div style="height: 48px;"></div>
                    @endif
                </div>
                <div class="sig-name">{{ $financeManagerName ?: 'Iswahyuni' }}</div>
                <div class="sig-role">{{ $financeManagerTitle ?: 'Manager Finance & Accounting' }}</div>
            </td>
            <td>
                <div class="sig-space">
                    @if(!empty($directorSignature))
                        <img src="{{ $directorSignature }}" class="sig-img" alt="TTD Direktur" />
                    @else
                        <div style="height: 48px;"></div>
                    @endif
                </div>
                <div class="sig-name">{{ $directorName ?: 'Ujang Peby Hidayat' }}</div>
                <div class="sig-role">{{ $directorTitle ?: 'Direktur' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>

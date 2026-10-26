<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Permohonan Peralatan Kerja - {{ $assetRequest->id }}</title>
    <style>
        @page {
            margin: 25px 35px 20px 35px;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.35;
            margin: 0;
            padding: 5px 10px;
        }

        .title-wrapper {
            text-align: center;
            margin-bottom: 22px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }

        .lead-text {
            font-size: 10pt;
            margin-bottom: 10px;
        }

        .bio-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 10pt;
        }
        .bio-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .bio-label {
            width: 160px;
        }
        .bio-separator {
            width: 18px;
            text-align: center;
        }
        .bio-value {
            border-bottom: 1px dotted #888;
            font-weight: normal;
        }

        .sub-caption {
            font-size: 9.5pt;
            margin-bottom: 14px;
        }

        .section-block {
            margin-bottom: 14px;
        }

        .checkbox-large {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #000;
            text-align: center;
            line-height: 15px;
            font-size: 10.5pt;
            font-weight: bold;
            vertical-align: middle;
            margin-right: 6px;
        }

        .checkbox-small {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1.2px solid #000;
            vertical-align: middle;
            margin-right: 6px;
        }

        .section-header {
            font-size: 10.5pt;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .item-spec-table {
            width: 100%;
            border-collapse: collapse;
            margin-left: 24px;
            margin-bottom: 8px;
            font-size: 9.5pt;
        }
        .item-spec-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .spec-label {
            width: 180px;
        }
        .spec-sep {
            width: 16px;
            text-align: center;
        }
        .spec-val {
            border-bottom: 1px dotted #888;
        }

        .reason-box {
            margin-left: 24px;
            border: 1.5px solid #000;
            padding: 6px 10px 8px 10px;
            min-height: 75px;
        }
        .reason-title {
            font-weight: bold;
            font-size: 9.5pt;
            margin-bottom: 4px;
        }
        .reason-content {
            font-size: 9.5pt;
            line-height: 1.5;
            color: #111;
        }
        .reason-line {
            border-bottom: 1px solid #777;
            height: 18px;
            margin-top: 2px;
        }

        .signatures-area {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures-area td {
            vertical-align: top;
        }

        .sig-block {
            text-align: center;
            font-size: 9.5pt;
        }
        .sig-space {
            height: 52px;
            text-align: center;
            vertical-align: middle;
        }
        .sig-img {
            max-height: 48px;
            max-width: 120px;
            display: inline-block;
            vertical-align: middle;
        }
        .bracket-box {
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .role-title {
            font-size: 9pt;
            margin-top: 4px;
        }
    </style>
</head>
<body>

    @php
        $isPergantian = ($assetRequest->request_type === 'pergantian_alat');
        $isPengadaanBaru = ($assetRequest->request_type === 'pengadaan_baru');
    @endphp

    <!-- JUDUL DOKUMEN -->
    <div class="title-wrapper">
        <h3 class="doc-title">FORMULIR PERMOHONAN PERALATAN KERJA</h3>
    </div>

    <!-- IDENTITAS PEMOHON -->
    <div class="lead-text">Saya yang bertanda tangan di bawah ini:</div>

    <table class="bio-table">
        <tr>
            <td class="bio-label">Nama Karyawan</td>
            <td class="bio-separator">:</td>
            <td class="bio-value"><strong>{{ $requesterName }}</strong></td>
        </tr>
        <tr>
            <td class="bio-label">Nomor Induk</td>
            <td class="bio-separator">:</td>
            <td class="bio-value">{{ $nip }}</td>
        </tr>
        <tr>
            <td class="bio-label">Golongan</td>
            <td class="bio-separator">:</td>
            <td class="bio-value">{{ $golongan }}</td>
        </tr>
        <tr>
            <td class="bio-label">Jabatan</td>
            <td class="bio-separator">:</td>
            <td class="bio-value">{{ $jabatan }}</td>
        </tr>
        <tr>
            <td class="bio-label">Departemen</td>
            <td class="bio-separator">:</td>
            <td class="bio-value">{{ $departemen }}</td>
        </tr>
        <tr>
            <td class="bio-label">Divisi</td>
            <td class="bio-separator">:</td>
            <td class="bio-value">{{ $divisi }}</td>
        </tr>
    </table>

    <div class="sub-caption">("Karyawan Yang Mengajukan")</div>

    <div class="lead-text" style="margin-bottom: 14px;">
        Dengan ini mengajukan permohonan pengadaan peralatan kerja untuk keperluan melaksanakan pekerjaan sebagai berikut:
    </div>

    <!-- 1. PERGANTIAN ALAT -->
    <div class="section-block">
        <div class="section-header">
            <span class="checkbox-large">{{ $isPergantian ? 'X' : '' }}</span> Pergantian Alat :
        </div>

        <table class="item-spec-table">
            <tr>
                <td class="spec-label">
                    <span class="checkbox-small"></span> Jenis Peralatan Kerja
                </td>
                <td class="spec-sep">:</td>
                <td class="spec-val">
                    {{ $isPergantian ? $assetRequest->item_name : '' }}
                </td>
            </tr>
            <tr>
                <td class="spec-label">
                    <span class="checkbox-small"></span> Merek
                </td>
                <td class="spec-sep">:</td>
                <td class="spec-val">
                    {{ $isPergantian ? ($assetRequest->brand ?? '-') : '' }}
                </td>
            </tr>
            <tr>
                <td class="spec-label">
                    <span class="checkbox-small"></span> Tipe
                </td>
                <td class="spec-sep">:</td>
                <td class="spec-val">
                    {{ $isPergantian ? ($assetRequest->model ?? '-') : '' }}
                </td>
            </tr>
            <tr>
                <td class="spec-label">
                    <span class="checkbox-small"></span> Spesifikasi
                </td>
                <td class="spec-sep">:</td>
                <td class="spec-val">
                    {{ $isPergantian ? ($assetRequest->specifications ?? '-') : '' }}
                </td>
            </tr>
        </table>

        <div class="reason-box">
            <div class="reason-title">Alasan Penggantian :</div>
            @if($isPergantian)
                <div class="reason-content">
                    {{ $assetRequest->purpose ?: $assetRequest->notes }}
                </div>
                <div class="reason-line"></div>
                <div class="reason-line"></div>
            @else
                <div class="reason-line"></div>
                <div class="reason-line"></div>
                <div class="reason-line"></div>
                <div class="reason-line"></div>
            @endif
        </div>
    </div>

    <!-- 2. PENGADAAN BARU -->
    <div class="section-block">
        <div class="section-header">
            <span class="checkbox-large">{{ $isPengadaanBaru ? 'X' : '' }}</span> Pengadaan Baru
        </div>

        <table class="item-spec-table">
            <tr>
                <td class="spec-label">
                    <span class="checkbox-small"></span> Jenis Peralatan Kerja
                </td>
                <td class="spec-sep">:</td>
                <td class="spec-val">
                    {{ $isPengadaanBaru ? $assetRequest->item_name : '' }}
                </td>
            </tr>
            <tr>
                <td class="spec-label">
                    <span class="checkbox-small"></span> Merek
                </td>
                <td class="spec-sep">:</td>
                <td class="spec-val">
                    {{ $isPengadaanBaru ? ($assetRequest->brand ?? '-') : '' }}
                </td>
            </tr>
            <tr>
                <td class="spec-label">
                    <span class="checkbox-small"></span> Tipe
                </td>
                <td class="spec-sep">:</td>
                <td class="spec-val">
                    {{ $isPengadaanBaru ? ($assetRequest->model ?? '-') : '' }}
                </td>
            </tr>
            <tr>
                <td class="spec-label">
                    <span class="checkbox-small"></span> Spesifikasi
                </td>
                <td class="spec-sep">:</td>
                <td class="spec-val">
                    {{ $isPengadaanBaru ? ($assetRequest->specifications ?? '-') : '' }}
                </td>
            </tr>
        </table>

        <div class="reason-box">
            <div class="reason-title">Alasan Pengadaan baru:</div>
            @if($isPengadaanBaru)
                <div class="reason-content">
                    {{ $assetRequest->purpose ?: $assetRequest->notes }}
                </div>
                <div class="reason-line"></div>
                <div class="reason-line"></div>
            @else
                <div class="reason-line"></div>
                <div class="reason-line"></div>
                <div class="reason-line"></div>
                <div class="reason-line"></div>
            @endif
        </div>
    </div>

    <div class="sub-caption" style="margin-top: 10px;">("Peralatan Kerja")</div>

    <div class="lead-text" style="margin-bottom: 12px;">
        Demikian permohonan pengadaan peralatan kerja ini saya ajukan.
    </div>

    <!-- TANGGAL & TANDA TANGAN YANG MENGAJUKAN -->
    <div style="font-size: 9.5pt; margin-bottom: 4px;">
        {{ $cityName }}, {{ $dateFormatted }}
    </div>
    <div style="font-size: 9.5pt; margin-bottom: 4px;">
        Yang Mengajukan,
    </div>
    <div class="sig-space" style="width: 180px; margin-bottom: 4px;">
        @if(!empty($requesterSignature))
            <img src="{{ $requesterSignature }}" class="sig-img" alt="TTD Pemohon" />
        @else
            <div style="height: 48px;"></div>
        @endif
    </div>
    <div style="font-size: 9.5pt; font-weight: bold; margin-bottom: 20px;">
        [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $requesterName }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]
    </div>

    <!-- APPROVALS (MENGETAHUI & MENYETUJUI) -->
    <table class="signatures-area">
        <tr>
            <td colspan="2" style="text-align: center; font-weight: 500; font-size: 10pt; padding-bottom: 6px;">
                Mengetahui,
            </td>
            <td style="text-align: center; font-weight: 500; font-size: 10pt; padding-bottom: 6px;">
                Menyetujui,
            </td>
        </tr>
        <tr>
            <td style="width: 33%; text-align: center;">
                <div class="sig-space">
                    @if(!empty($hrManagerSignature))
                        <img src="{{ $hrManagerSignature }}" class="sig-img" alt="TTD HR" />
                    @else
                        <div style="height: 48px;"></div>
                    @endif
                </div>
                <div class="bracket-box">[&nbsp;&nbsp;&nbsp;&nbsp;{{ $hrManagerName ?: '.........................' }}&nbsp;&nbsp;&nbsp;&nbsp;]</div>
                <div class="role-title">Manajer HR & GA,</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <div class="sig-space">
                    @if(!empty($accountingManagerSignature))
                        <img src="{{ $accountingManagerSignature }}" class="sig-img" alt="TTD Accounting" />
                    @else
                        <div style="height: 48px;"></div>
                    @endif
                </div>
                <div class="bracket-box">[&nbsp;&nbsp;&nbsp;&nbsp;{{ $accountingManagerName ?: '.........................' }}&nbsp;&nbsp;&nbsp;&nbsp;]</div>
                <div class="role-title">Manajer Accounting,</div>
            </td>
            <td style="width: 34%; text-align: center;">
                <div class="sig-space">
                    @if(!empty($directorSignature))
                        <img src="{{ $directorSignature }}" class="sig-img" alt="TTD Direktur" />
                    @else
                        <div style="height: 48px;"></div>
                    @endif
                </div>
                <div class="bracket-box">[&nbsp;&nbsp;&nbsp;&nbsp;{{ $directorName ?: '.........................' }}&nbsp;&nbsp;&nbsp;&nbsp;]</div>
                <div class="role-title">Direktur,</div>
            </td>
        </tr>
    </table>

</body>
</html>

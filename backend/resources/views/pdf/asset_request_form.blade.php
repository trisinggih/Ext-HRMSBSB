<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Kebutuhan Perlengkapan Kantor - {{ $assetRequest->id }}</title>
    <style>
        @page {
            margin: 20px 30px;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.3;
            margin: 0;
            padding: 10px 15px;
        }

        /* HEADER / KOP SURAT */
        .header-banner {
            background-color: #0c768a;
            border-bottom: 3.5px solid #f59e0b;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-col {
            width: 25%;
            text-align: left;
            vertical-align: middle;
        }
        .logo-col img {
            max-width: 140px;
            max-height: 60px;
            object-fit: contain;
            display: block;
        }
        .company-info-col {
            width: 75%;
            text-align: center;
            padding-left: 10px;
            vertical-align: middle;
        }
        .company-name {
            font-size: 14pt;
            font-weight: 900;
            letter-spacing: 1.5px;
            color: #ffffff;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .company-sub {
            font-size: 9pt;
            font-weight: bold;
            color: #fef08a;
            margin: 0 0 3px 0;
            letter-spacing: 0.5px;
        }
        .company-address {
            font-size: 7.5pt;
            color: #f1f5f9;
            margin: 0 0 2px 0;
            line-height: 1.25;
        }
        .company-contact {
            font-size: 7.5pt;
            color: #e2e8f0;
            margin: 0;
        }
        .double-line {
            display: none;
        }

        /* TITLE */
        .title-wrapper {
            text-align: center;
            margin-bottom: 18px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        /* METADATA INFO */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9.5pt;
        }
        .meta-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .meta-label {
            width: 120px;
            color: #000;
        }
        .meta-separator {
            width: 15px;
            text-align: center;
        }
        .meta-value {
            font-weight: normal;
        }
        .italic-label {
            font-style: italic;
        }

        /* DATA TABLE */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            font-size: 9pt;
        }
        .content-table th, .content-table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .content-table th {
            text-align: center;
            font-weight: bold;
            background-color: #fff;
            text-transform: uppercase;
        }
        .table-category-header {
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
            padding: 5px !important;
            font-size: 9.5pt;
            background-color: #fff;
        }
        .col-no {
            width: 5%;
            text-align: center;
        }
        .col-item {
            width: 38%;
            text-align: left;
        }
        .col-qty {
            width: 10%;
            text-align: center;
        }
        .col-placement {
            width: 23%;
            text-align: left;
        }
        .col-price {
            width: 24%;
            text-align: right;
        }
        .item-spec {
            font-size: 8pt;
            color: #444;
            margin-top: 2px;
        }
        .total-row td {
            font-weight: bold;
            padding: 7px 8px;
        }
        .total-label {
            text-align: center;
            font-size: 9.5pt;
        }

        /* SIGNATURES */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            margin-top: 25px;
        }
        .signatures-table td {
            width: 33.33%;
            vertical-align: top;
            padding: 0 10px;
        }
        .sig-title {
            font-size: 10pt;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .sig-space {
            height: 60px;
            text-align: center;
            vertical-align: middle;
            margin-bottom: 5px;
        }
        .sig-img {
            max-height: 58px;
            max-width: 130px;
            display: inline-block;
            vertical-align: middle;
        }
        .sig-placeholder {
            height: 58px;
        }
        .sig-name {
            font-size: 10pt;
            margin: 0;
            white-space: nowrap;
        }
        .sig-role {
            font-size: 8.5pt;
            color: #555;
            margin-top: 3px;
        }
    </style>
</head>
<body>

    @php
        $branch = $assetRequest->branch 
            ?? ($assetRequest->requester?->assignments?->where('is_primary', true)->first()?->branch 
                ?? $assetRequest->requester?->branch 
                ?? $assetRequest->company?->branches?->first());
        $company = $assetRequest->company 
            ?? $branch?->company 
            ?? $assetRequest->requester?->company;
        $group = \App\Models\CompanyGroup::first();

        // Resolusi Logo: Prioritas Cabang -> fallback Company -> fallback Group
        $logoBase64 = null;
        if ($branch && method_exists($branch, 'getEffectiveLogoBase64Attribute')) {
            $logoBase64 = $branch->effective_logo_base64;
        } elseif ($company && method_exists($company, 'getEffectiveLogoBase64Attribute')) {
            $logoBase64 = $company->effective_logo_base64;
        }
        if (!$logoBase64 && $group && $group->logo && file_exists(storage_path('app/public/' . $group->logo))) {
            $p = storage_path('app/public/' . $group->logo);
            $mime = mime_content_type($p) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
        }

        $branchNameDisplay = $branch?->name ? strtoupper($branch->name) : ($company?->name ?? 'JANGEBY GROUP');
        $companyLegal = $company?->legal_name ?: ($company?->name ?? 'JANGEBY GROUP');
        $address = $branch?->address ?: ($company?->address ?? 'Kota Depok, Jawa Barat');
        $city = $branch?->city ?: ($company?->city ?? 'Depok');
        $postal = $branch?->postal_code ?: ($company?->postal_code ?? '');
        $phone = $branch?->phone ?: ($company?->phone ?? '-');
        $email = $branch?->email ?: ($company?->email ?? '-');
    @endphp

    <!-- KOP SURAT BERDASARKAN CABANG -->
    <div class="header-banner">
        <table class="header-table">
            <tr>
                <td class="logo-col">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="{{ $branchNameDisplay }}">
                    @else
                        <div style="background: rgba(0,0,0,0.25); color: #fef08a; padding: 8px; font-weight: bold; font-size: 11pt; text-align: center; border-radius: 4px;">
                            {{ substr($branchNameDisplay, 0, 15) }}
                        </div>
                    @endif
                </td>
                <td class="company-info-col">
                    <div class="company-name">{{ $branchNameDisplay }}</div>
                    <div class="company-sub">{{ $companyLegal }}</div>
                    <div class="company-address">{{ $address }}{{ $city ? ', ' . $city : '' }}{{ $postal ? ' ' . $postal : '' }}</div>
                    <div class="company-contact">@if($email && $email !== '-') Email : {{ $email }} &nbsp;&bull;&nbsp; @endif Telp : {{ $phone }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="title-wrapper">
        <h3 class="doc-title">PENGAJUAN KEBUTUHAN PERLENGKAPAN KANTOR</h3>
    </div>

    <!-- METADATA FORM -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Company</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ $company?->name ?? ($assetRequest->company->name ?? 'JANGEBY GROUP') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Cabang</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ $branch?->name ?? ($assetRequest->branch->name ?? '-') }}</td>
        </tr>
        <tr>
            <td class="meta-label italic-label">DEPARTEMEN</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ $assetRequest->department->name ?? ($assetRequest->requester->department->name ?? '-') }}</td>
        </tr>
        <tr>
            <td class="meta-label italic-label">MANAGER</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ $managerName ?? ($assetRequest->manager->name ?? ($assetRequest->department->manager->name ?? '...')) }}</td>
        </tr>
        <tr>
            <td class="meta-label italic-label">Bulan</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">
                @if(!empty($assetRequest->submission_month))
                    {{ $assetRequest->submission_month }}
                @else
                    {{ \Carbon\Carbon::parse($assetRequest->created_at)->translatedFormat('F Y') }}
                @endif
            </td>
        </tr>
    </table>

    <!-- TABEL PENGAJUAN ITEM -->
    <table class="content-table">
        <thead>
            <tr>
                <th colspan="5" class="table-category-header">
                    @if($assetRequest->request_type === 'kebutuhan_karyawan_baru')
                        PENGAJUAN KEBUTUHAN ASET KARYAWAN BARU
                    @elseif($assetRequest->request_type === 'kebutuhan_perbaikan')
                        PENGAJUAN PERBAIKAN LAMPU, KABEL & SARANA
                    @else
                        PENGAJUAN ITEM RUMAH TANGGA
                    @endif
                </th>
            </tr>
            <tr>
                <th class="col-no">NO</th>
                <th class="col-item">ITEM</th>
                <th class="col-qty">QTY</th>
                <th class="col-placement">PENEMPATAN</th>
                <th class="col-price">ESTIMASI HARGA TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php
                $rows = [];
                if ($assetRequest->items && $assetRequest->items->count() > 0) {
                    foreach($assetRequest->items as $item) {
                        $rows[] = [
                            'name' => $item->item_name,
                            'size' => $item->item_size,
                            'qty' => $item->quantity,
                            'placement' => $item->placement ?: $assetRequest->for_who,
                            'price' => $item->estimated_price,
                        ];
                    }
                } else {
                    $rows[] = [
                        'name' => $assetRequest->item_name,
                        'size' => $assetRequest->item_size ?: $assetRequest->specifications,
                        'qty' => $assetRequest->quantity,
                        'placement' => $assetRequest->placement ?: $assetRequest->for_who,
                        'price' => $assetRequest->estimated_price,
                    ];
                }

                $totalPrice = 0;
                foreach($rows as $r) {
                    $totalPrice += floatval($r['price'] ?? 0);
                }

                // Format minimal 5 baris persis seperti di gambar template
                $rowCount = count($rows);
                $minRows = max(5, $rowCount);
            @endphp

            @for ($i = 0; $i < $minRows; $i++)
                @if (isset($rows[$i]))
                    <tr>
                        <td class="col-no">{{ $i + 1 }}</td>
                        <td class="col-item">
                            <strong>{{ $rows[$i]['name'] }}</strong>
                            @if(!empty($rows[$i]['size']))
                                <div class="item-spec">Ukuran / Keterangan: {{ $rows[$i]['size'] }}</div>
                            @endif
                        </td>
                        <td class="col-qty">{{ $rows[$i]['qty'] }}</td>
                        <td class="col-placement">{{ $rows[$i]['placement'] ?? '-' }}</td>
                        <td class="col-price">
                            @if(!empty($rows[$i]['price']) && $rows[$i]['price'] > 0)
                                Rp {{ number_format($rows[$i]['price'], 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @else
                    <tr>
                        <td class="col-no">{{ $i + 1 }}</td>
                        <td class="col-item">&nbsp;</td>
                        <td class="col-qty">&nbsp;</td>
                        <td class="col-placement">&nbsp;</td>
                        <td class="col-price">&nbsp;</td>
                    </tr>
                @endif
            @endfor

            <!-- TOTAL BARIS -->
            <tr class="total-row">
                <td colspan="4" class="total-label">Total Estimasi Harga</td>
                <td class="col-price">
                    @if($totalPrice > 0)
                        Rp {{ number_format($totalPrice, 0, ',', '.') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    @php
        $reqSig = $requesterSignature ?? $assetRequest->requester?->signature_base64;
        $mgrSig = $managerSignature ?? ($assetRequest->acknowledgedBy?->signature_base64 ?? $assetRequest->manager?->signature_base64);
        $appSig = $approverSignature ?? ($assetRequest->approvedBy?->signature_base64 ?? ($assetRequest->approvals?->where('level', 2)->first()?->approver?->signature_base64 ?? null));
    @endphp

    <!-- TANDA TANGAN (DIAJUKAN, MENGETAHUI, DISETUJUI) -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-title">Diajukan,</div>
                <div class="sig-space">
                    @if(!empty($reqSig))
                        <img src="{{ $reqSig }}" class="sig-img" alt="TTD Pemohon" />
                    @else
                        <div class="sig-placeholder"></div>
                    @endif
                </div>
                <div class="sig-name">( {{ $assetRequest->requester->name ?? '....................................' }} )</div>
                <div class="sig-role">Pemohon</div>
            </td>
            <td>
                <div class="sig-title">Mengetahui,</div>
                <div class="sig-space">
                    @if(!empty($mgrSig))
                        <img src="{{ $mgrSig }}" class="sig-img" alt="TTD Mengetahui" />
                    @else
                        <div class="sig-placeholder"></div>
                    @endif
                </div>
                <div class="sig-name">( {{ $managerName ?? ($assetRequest->acknowledgedBy->name ?? ($assetRequest->manager->name ?? '....................................')) }} )</div>
                <div class="sig-role">Manager Departemen</div>
            </td>
            <td>
                <div class="sig-title">Disetujui,</div>
                <div class="sig-space">
                    @if(!empty($appSig))
                        <img src="{{ $appSig }}" class="sig-img" alt="TTD Disetujui" />
                    @else
                        <div class="sig-placeholder"></div>
                    @endif
                </div>
                <div class="sig-name">( {{ $approverName ?? ($assetRequest->approvedBy->name ?? '....................................') }} )</div>
                <div class="sig-role">Direktur / Finance</div>
            </td>
        </tr>
    </table>

</body>
</html>

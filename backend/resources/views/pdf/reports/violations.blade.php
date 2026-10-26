<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pelanggaran & Surating Peringatan (SP)</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px; }
        th { background-color: #f4f4f4; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #dc3545; font-weight: bold; }
        .badge-active { color: #dc3545; font-weight: bold; }
        .badge-expired { color: #6c757d; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p><b>LAPORAN SURAT PERINGATAN (SP) & PELANGGARAN</b></p>
        <p>
            Periode: {{ $request->start_date ?? '-' }} s/d {{ $request->end_date ?? '-' }} | 
            Tingkat SP: {{ $request->warning_level ? strtoupper($request->warning_level) : 'Semua' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>No Surat</th>
                <th>Tanggal Terbit</th>
                <th>NIK</th>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th class="text-center">Tingkat SP</th>
                <th>Jenis Pelanggaran / Deskripsi</th>
                <th class="text-center">Berlaku Sampai</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->letter_number ?? '-' }}</td>
                    <td class="text-center">{{ $item->issue_date ?? $item->created_at->format('Y-m-d') }}</td>
                    <td>{{ $item->employee->nip ?? $item->employee->employee_id ?? '-' }}</td>
                    <td><b>{{ $item->employee->name ?? '-' }}</b></td>
                    <td>{{ $item->employee->primaryAssignment->department->name ?? '-' }}</td>
                    <td class="text-center text-danger">{{ strtoupper($item->warning_level ?? $item->type ?? 'SP 1') }}</td>
                    <td>{{ $item->description ?? $item->reason ?? '-' }}</td>
                    <td class="text-center">{{ $item->expiry_date ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge-{{ ($item->expiry_date && $item->expiry_date >= date('Y-m-d')) ? 'active' : 'expired' }}">
                            {{ ($item->expiry_date && $item->expiry_date >= date('Y-m-d')) ? 'AKTIF' : 'EXPIRED' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data pelanggaran / SP pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

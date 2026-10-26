<!DOCTYPE html>
<html>
<head>
    <title>Laporan Cuti</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p>Laporan Cuti Karyawan</p>
        <p>
            Periode Pengajuan: {{ $request->start_date }} s/d {{ $request->end_date }} | 
            Status = {{ $request->status ? ucfirst($request->status) : 'Semua' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th>Nama Karyawan</th>
                <th>Tipe Cuti</th>
                <th>Tgl Mulai</th>
                <th>Tgl Selesai</th>
                <th class="text-center">Lama</th>
                <th>Keterangan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><b>{{ $item->employee ? $item->employee->name : '-' }}</b></td>
                    <td>{{ $item->leaveType ? $item->leaveType->name : '-' }}</td>
                    <td class="text-center">{{ $item->start_date }}</td>
                    <td class="text-center">{{ $item->end_date }}</td>
                    <td class="text-center">{{ $item->total_days }} Hari</td>
                    <td>{{ $item->reason }}</td>
                    <td class="text-center">
                        @if($item->status == 'approved')
                            Disetujui
                        @elseif($item->status == 'rejected')
                            Ditolak
                        @else
                            Menunggu
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data cuti.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

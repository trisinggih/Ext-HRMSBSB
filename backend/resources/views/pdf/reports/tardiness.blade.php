<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keterlambatan Karyawan</title>
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
        .text-danger { color: #dc3545; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p><b>LAPORAN KETERLAMBATAN KARYAWAN</b></p>
        <p>
            Periode: {{ $request->start_date }} s/d {{ $request->end_date }} | 
            Departemen: {{ $request->department_id ? \App\Models\Department::find($request->department_id)->name : 'Semua' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Tanggal</th>
                <th>NIK</th>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th>Jam Clock In</th>
                <th class="text-center">Menit Telat</th>
                <th>Alasan / Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item->date }}</td>
                    <td>{{ $item->employee->nip ?? $item->employee->employee_id ?? '-' }}</td>
                    <td><b>{{ $item->employee->name ?? '-' }}</b></td>
                    <td>{{ $item->employee->primaryAssignment->department->name ?? '-' }}</td>
                    <td class="text-center">{{ $item->clock_in_at ? \Carbon\Carbon::parse($item->clock_in_at)->format('H:i:s') : '-' }}</td>
                    <td class="text-center fw-bold text-danger">{{ $item->late_minutes }} Menit</td>
                    <td>{{ $item->remarks ?? $item->reason ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data keterlambatan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

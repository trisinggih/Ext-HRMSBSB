<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kehadiran</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 11px; }
        th { background-color: #f4f4f4; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p>Laporan Kehadiran Karyawan</p>
        <p>
            Periode: {{ $request->start_date }} s/d {{ $request->end_date }} | 
            Departemen = {{ $request->department_id ? \App\Models\Department::find($request->department_id)->name : 'Semua' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30" rowspan="2" class="text-center">No</th>
                <th rowspan="2">NIK</th>
                <th rowspan="2">Nama Karyawan</th>
                <th rowspan="2">Departemen</th>
                <th colspan="4" class="text-center">Total Kehadiran</th>
            </tr>
            <tr>
                <th class="text-center">Hadir</th>
                <th class="text-center">Telat</th>
                <th class="text-center">Mangkir</th>
                <th class="text-center">Cuti/Izin</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->employee_code }}</td>
                    <td><b>{{ $item->name }}</b></td>
                    <td>{{ $item->department_name }}</td>
                    <td class="text-center">{{ $item->total_present }}</td>
                    <td class="text-center">{{ $item->total_late }}</td>
                    <td class="text-center">{{ $item->total_absent }}</td>
                    <td class="text-center">{{ $item->total_leave }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data kehadiran.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

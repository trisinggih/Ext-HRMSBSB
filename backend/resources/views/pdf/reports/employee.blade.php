<!DOCTYPE html>
<html>
<head>
    <title>Laporan Data Karyawan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p>Laporan Data Karyawan</p>
        <p>
            Filter: 
            Status = {{ $request->status ? ucfirst($request->status) : 'Semua' }} | 
            Departemen = {{ $request->department_id ? \App\Models\Department::find($request->department_id)->name : 'Semua' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th>NIK</th>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th>Jabatan</th>
                <th>Status</th>
                <th>Tgl Bergabung</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->employee_code }}</td>
                    <td><b>{{ $item->name }}</b></td>
                    <td>{{ $item->department ? $item->department->name : '-' }}</td>
                    <td>{{ $item->position ? $item->position->name : '-' }}</td>
                    <td>{{ ucfirst($item->employment_status) }}</td>
                    <td>{{ $item->join_date }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data karyawan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

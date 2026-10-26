<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penggajian</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-danger { color: #d9534f; }
        .text-success { color: #5cb85c; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p>Laporan Penggajian Karyawan</p>
        <p>
            Periode: {{ $period ? $period->name : '-' }} | 
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
                <th class="text-end">Penerimaan Kotor</th>
                <th class="text-end">Total Potongan</th>
                <th class="text-end">BPJS (Karyawan)</th>
                <th class="text-end">Gaji Bersih (THP)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumGross = 0;
                $sumDeduction = 0;
                $sumBpjs = 0;
                $sumNet = 0;
            @endphp
            @forelse($data as $index => $item)
                @php 
                    $sumGross += $item->gross_salary;
                    $sumDeduction += $item->total_deduction;
                    $sumBpjs += $item->total_bpjs;
                    $sumNet += $item->net_salary;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->employee ? $item->employee->employee_code : '-' }}</td>
                    <td><b>{{ $item->employee ? $item->employee->name : '-' }}</b></td>
                    <td>{{ $item->employee && $item->employee->department ? $item->employee->department->name : '-' }}</td>
                    <td class="text-end">Rp {{ number_format($item->gross_salary, 0, ',', '.') }}</td>
                    <td class="text-end text-danger">Rp {{ number_format($item->total_deduction, 0, ',', '.') }}</td>
                    <td class="text-end text-danger">Rp {{ number_format($item->total_bpjs, 0, ',', '.') }}</td>
                    <td class="text-end text-success fw-bold">Rp {{ number_format($item->net_salary, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data penggajian.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($data) > 0)
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">TOTAL KESELURUHAN</th>
                <th class="text-end">Rp {{ number_format($sumGross, 0, ',', '.') }}</th>
                <th class="text-end text-danger">Rp {{ number_format($sumDeduction, 0, ',', '.') }}</th>
                <th class="text-end text-danger">Rp {{ number_format($sumBpjs, 0, ',', '.') }}</th>
                <th class="text-end text-success fw-bold">Rp {{ number_format($sumNet, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>

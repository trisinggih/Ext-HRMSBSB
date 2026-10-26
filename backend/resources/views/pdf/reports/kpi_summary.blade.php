<!DOCTYPE html>
<html>
<head>
    <title>Laporan Rekapitulasi Evaluasi KPI Karyawan</title>
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
        .badge-success { color: #198754; font-weight: bold; }
        .badge-info { color: #0dcaf0; font-weight: bold; }
        .badge-warning { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p><b>LAPORAN REKAPITULASI KPI KARYAWAN</b></p>
        <p>
            Tahun: {{ $request->year ?? date('Y') }} | 
            Periode: {{ $request->period ? strtoupper($request->period) : 'Semua' }} |
            Tipe KPI: {{ $request->kpi_type ? strtoupper($request->kpi_type) : 'Semua' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>NIK</th>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th>Jabatan</th>
                <th class="text-center">Tipe Evaluation</th>
                <th class="text-center">Periode / Bulan</th>
                <th class="text-center">Skor Akhir</th>
                <th class="text-center">Grade / Kategori</th>
                <th class="text-center">Status Appr.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->employee->nip ?? $item->employee->employee_id ?? '-' }}</td>
                    <td><b>{{ $item->employee->name ?? '-' }}</b></td>
                    <td>{{ $item->employee->primaryAssignment->department->name ?? '-' }}</td>
                    <td>{{ $item->employee->primaryAssignment->position->name ?? '-' }}</td>
                    <td class="text-center">{{ $item->type ?? 'Existing' }}</td>
                    <td class="text-center">{{ $item->period ?? $item->month ?? '-' }} {{ $item->year ?? '' }}</td>
                    <td class="text-center fw-bold">{{ number_format($item->final_score ?? $item->score ?? 0, 2) }}</td>
                    <td class="text-center fw-bold">{{ $item->grade ?? $item->rating ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge-{{ $item->status === 'approved' || $item->status === 'completed' ? 'success' : 'warning' }}">
                            {{ strtoupper($item->status ?? 'draft') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data KPI pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

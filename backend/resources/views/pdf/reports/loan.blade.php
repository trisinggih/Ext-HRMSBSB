<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kasbon</title>
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
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company->name ?? 'HRMS Company' }}</h2>
        <p>Laporan Kasbon / Pinjaman Karyawan</p>
        <p>
            Status = {{ $request->status ? ($request->status == 'paid' ? 'Lunas' : 'Belum Lunas') : 'Semua' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th class="text-end">Total Pinjaman</th>
                <th class="text-end">Cicilan / Bulan</th>
                <th class="text-end">Sisa Saldo</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumLoan = 0;
                $sumBalance = 0;
            @endphp
            @forelse($data as $index => $item)
                @php 
                    $sumLoan += $item->total_loan;
                    $sumBalance += $item->remaining_balance;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><b>{{ $item->employee ? $item->employee->name : '-' }}</b></td>
                    <td>{{ $item->employee && $item->employee->department ? $item->employee->department->name : '-' }}</td>
                    <td class="text-end">Rp {{ number_format($item->total_loan, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($item->installment_amount, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold text-danger">Rp {{ number_format($item->remaining_balance, 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if($item->status == 'paid')
                            Lunas
                        @else
                            Belum Lunas
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data kasbon.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($data) > 0)
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">TOTAL KESELURUHAN</th>
                <th class="text-end">Rp {{ number_format($sumLoan, 0, ',', '.') }}</th>
                <th class="text-end">-</th>
                <th class="text-end fw-bold text-danger">Rp {{ number_format($sumBalance, 0, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>

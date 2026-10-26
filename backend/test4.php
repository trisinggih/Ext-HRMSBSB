<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;

$startDate = '2026-10-01';
$endDate = '2026-10-04';

$query = Employee::query()->distinct()->with([
    'shifts' => function($q) use ($startDate, $endDate) {
        $q->where('effective_date', '<=', $endDate)
          ->where(function($sq) use ($startDate) {
              $sq->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
          })->where('is_active', true)
          ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END ASC')
          ->orderByDesc('effective_date')
          ->with('shift');
    }
]);

$emp = $query->first();

$start = \Carbon\Carbon::parse($startDate);
$end = \Carbon\Carbon::parse($endDate);

for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
    $dateStr = $d->toDateString();
    
    $matchingShifts = $emp->shifts->filter(function($s) use ($dateStr) {
        $effDate = $s->effective_date instanceof \Carbon\Carbon
            ? $s->effective_date->toDateString()
            : (string) $s->effective_date;
        $endDate2 = $s->end_date ? ($s->end_date instanceof \Carbon\Carbon
            ? $s->end_date->toDateString()
            : (string) $s->end_date) : null;
        return $effDate <= $dateStr && (!$endDate2 || $endDate2 >= $dateStr);
    });
    
    $empShift = $matchingShifts->first(fn($s) => !is_null($s->end_date))
             ?? $matchingShifts->first();

    $shiftName = $empShift ? ($empShift->shift?->name ?? 'Off / Libur') : 'Belum Ada Jadwal / Libur';
    echo "Date: $dateStr | Shift: $shiftName\n";
}

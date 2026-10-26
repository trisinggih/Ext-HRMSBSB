<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = App\Models\EmployeeShift::where('effective_date', '>=', '2026-01-01')->where('effective_date', '<=', '2026-01-31')->count();
echo "Jan Shifts: " . $count . "\n";

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shifts = App\Models\EmployeeShift::select('effective_date', 'end_date')->distinct()->get();
foreach ($shifts as $s) {
    echo $s->effective_date->toDateString() . " to " . ($s->end_date ? $s->end_date->toDateString() : 'null') . "\n";
}

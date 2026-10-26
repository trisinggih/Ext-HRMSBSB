<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = App\Models\EmployeeShift::first();
echo $s->effective_date->toDateString() . " || " . $s->getRawOriginal('effective_date') . "\n";

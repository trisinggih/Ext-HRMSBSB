<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$comp = App\Models\PayrollComponent::where('code', 'kerajinan')->first();
echo "is_separate_payslip: " . $comp->is_separate_payslip . "\n";

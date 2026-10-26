<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$comp = App\Models\PayrollComponent::where('code', 'kerajinan')->first();
echo "calculation_source: " . $comp->calculation_source . "\n";
echo "calculation_method: " . $comp->calculation_method . "\n";

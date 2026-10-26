<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$comp = App\Models\PayrollComponent::where('code', 'kerajinan')->first();
echo "has_rules: " . $comp->has_rules . "\n";
echo "formula: " . $comp->formula . "\n";

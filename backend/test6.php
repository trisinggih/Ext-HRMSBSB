<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$comp = App\Models\PayrollComponent::where('code', 'kerajinan')->first();
if ($comp) {
    echo "Component: " . $comp->name . "\n";
    echo "Schema: " . json_encode($comp->payment_schema) . "\n";
} else {
    echo "kerajinan not found\n";
}

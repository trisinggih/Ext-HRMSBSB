<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PayrollComponent;

$comps = PayrollComponent::get();
echo "=== ALL PAYROLL COMPONENTS & PAYMENT SCHEMA ===\n";
foreach ($comps as $c) {
    $schema = is_array($c->payment_schema) ? json_encode($c->payment_schema) : ($c->payment_schema ?? 'null');
    echo "ID: {$c->id} | Code: {$c->code} | Name: {$c->name} | Schema: {$schema}\n";
}

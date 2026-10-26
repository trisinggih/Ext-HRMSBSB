<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\EmployeeSalaryComponent;
use Illuminate\Support\Facades\DB;

$rows = EmployeeSalaryComponent::where('employee_id', 231)->with('component')->get();

echo "All components for Emp 231 and their effective_from dates:\n";
foreach ($rows as $r) {
    $code = $r->component->code ?? 'N/A';
    echo "ID: {$r->id} | Comp: {$code} | Amount: {$r->amount} | EffectiveFrom: {$r->effective_from} | Active: {$r->is_active}\n";
}

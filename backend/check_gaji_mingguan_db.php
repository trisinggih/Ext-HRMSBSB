<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PayrollComponent;
use App\Models\EmployeeSalaryComponent;
use App\Models\Employee;

echo "=== PAYROLL COMPONENTS FOR MINGGUAN & HARIAN ===\n";
$comps = PayrollComponent::whereIn('code', ['gaji_harian', 'gaji_mingguan'])->get();
foreach ($comps as $c) {
    echo "ID: {$c->id} | Code: {$c->code} | Name: {$c->name} | HasRules: {$c->has_rules} | Formula: {$c->formula} | CalcType: {$c->calculation_type} | Schema: " . json_encode($c->payment_schema) . "\n";
}

echo "\n=== EMPLOYEE SALARY COMPONENTS FOR ADEN BINTANG ===\n";
$emp = Employee::where('name', 'like', '%Aden Bintang%')->first();
if ($emp) {
    echo "Employee ID: {$emp->id} | Name: {$emp->name}\n";
    $esc = EmployeeSalaryComponent::where('employee_id', $emp->id)->with('component')->get();
    foreach ($esc as $e) {
        $code = $e->component->code ?? 'N/A';
        $name = $e->component->name ?? 'N/A';
        echo "CompID: {$e->payroll_component_id} | Code: {$code} ({$name}) | CalcType: {$e->calculation_type} | Amount: {$e->amount} | Formula: {$e->formula}\n";
    }
} else {
    echo "Employee Aden Bintang not found.\n";
}

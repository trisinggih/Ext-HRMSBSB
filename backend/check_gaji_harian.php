<?php
$comp = App\Models\PayrollComponent::where("code", "gaji_harian")->first();
if ($comp) {
    echo "Code: " . $comp->code . "\n";
    echo "Name: " . $comp->name . "\n";
    echo "Active: " . $comp->is_active . "\n";
    echo "Type: " . $comp->type . "\n";
    echo "Category: " . $comp->category . "\n";
    echo "Calc Type: " . $comp->calculation_type . "\n";
    echo "Show on payslip: " . $comp->show_on_payslip . "\n";
} else {
    echo "Not found";
}


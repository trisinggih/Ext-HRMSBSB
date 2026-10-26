<?php
$comp = App\Models\PayrollComponent::where("name", "like", "%Gaji Mingguan%")->first();
if ($comp) {
    echo "Code: " . $comp->code . "\n";
    echo "Formula: " . $comp->formula . "\n";
} else {
    echo "Not found by name. Checking code gaji_harian_per_bulan:\n";
    $comp = App\Models\PayrollComponent::where("code", "gaji_harian_per_bulan")->first();
    echo "Code: " . $comp->code . "\n";
    echo "Name: " . $comp->name . "\n";
    echo "Formula: " . $comp->formula . "\n";
}


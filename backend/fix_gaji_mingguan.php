<?php
$comp = App\Models\PayrollComponent::where("code", "gaji_mingguan")->first();
if ($comp) {
    $comp->has_rules = 1;
    $comp->save();
    echo "gaji_mingguan has_rules updated to 1.\n";
}


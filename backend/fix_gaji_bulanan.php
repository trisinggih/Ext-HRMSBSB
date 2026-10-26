<?php
$comp = App\Models\PayrollComponent::where("code", "gaji_bulanan")->first();
if ($comp) {
    $comp->has_rules = 0;
    $comp->save();
    echo "gaji_bulanan has_rules updated to 0.\n";
}


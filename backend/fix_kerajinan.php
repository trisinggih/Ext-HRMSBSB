<?php
$comp = App\Models\PayrollComponent::where("code", "kerajinan")->first();
if ($comp) {
    $comp->has_rules = 0;
    $comp->save();
    echo "kerajinan has_rules updated to 0.\n";
}


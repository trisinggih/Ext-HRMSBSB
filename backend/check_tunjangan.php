<?php
$comps = App\Models\PayrollComponent::whereIn("code", ["gaji_bulanan", "tunjangan_bulanan"])->get();
foreach($comps as $c) {
    echo "Code: " . $c->code . " | Name: " . $c->name . " | has_rules: " . $c->has_rules . " | formula: " . $c->formula . "\n";
}


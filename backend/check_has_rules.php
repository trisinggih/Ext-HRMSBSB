<?php
$comp = App\Models\PayrollComponent::where("code", "gaji_mingguan")->first();
echo "has_rules: " . $comp->has_rules . "\n";


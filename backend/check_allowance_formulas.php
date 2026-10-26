<?php
$codes = ["tunjangan_bulanan", "tunjangan_transport", "tunjangan_jabatan", "tunjangan_makan"];
foreach($codes as $code) {
    $comp = App\Models\PayrollComponent::where("code", $code)->first();
    if($comp) {
        echo $code . " -> formula: " . $comp->formula . ", has_rules: " . $comp->has_rules . "\n";
    }
}


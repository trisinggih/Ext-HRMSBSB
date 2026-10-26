<?php
$comp = App\Models\PayrollComponent::where("code", "gaji_pokok_prorata")->first();
if ($comp) {
    $comp->is_active = 0;
    $comp->show_on_payslip = 0;
    $comp->save();
    echo "gaji_pokok_prorata disabled.\n";
}


<?php
$comp = App\Models\PayrollComponent::where("name", "Gaji Pokok Prorata (Standar Depnaker)")->first();
if ($comp) {
    echo $comp->code . " - show_on_payslip: " . $comp->show_on_payslip;
}


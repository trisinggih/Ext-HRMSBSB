<?php
$comps = App\Models\PayrollComponent::whereJsonContains("payment_schema", "MONTHLY_PAID_MONTHLY")->get();
foreach($comps as $c) {
    echo $c->code . " - " . $c->name . " - is_active:" . $c->is_active . " - show_on_payslip:" . $c->show_on_payslip . "\n";
}


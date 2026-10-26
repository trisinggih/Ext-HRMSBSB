<?php
$components = App\Models\PayrollComponent::all();
foreach($components as $c) {
    echo $c->code . " -> category: " . $c->category . ", is_allowance: " . (isset($c->is_allowance) ? $c->is_allowance : "N/A") . "\n";
}


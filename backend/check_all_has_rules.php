<?php
$comps = App\Models\PayrollComponent::all();
foreach($comps as $c) {
    echo $c->code . " -> has_rules: " . $c->has_rules . ", formula: " . $c->formula . "\n";
}


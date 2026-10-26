<?php
$comps = App\Models\PayrollComponent::where("code", "like", "%tunjangan%")->get();
foreach($comps as $c) {
    echo $c->code . " -> " . $c->name . "\n";
}


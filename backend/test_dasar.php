<?php
$comps = App\Models\PayrollComponent::where("code", "like", "%dasar%")->get();
foreach($comps as $c) {
    echo $c->code . " -> " . $c->name . "\n";
}


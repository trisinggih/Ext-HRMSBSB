<?php
$emp = App\Models\Employee::find(139);
$comps = App\Models\EmployeeSalaryComponent::where("employee_id", 139)->with("component")->get();
foreach ($comps as $c) {
    if ($c->component) {
        echo $c->component->code . " -> Amount: " . $c->amount . "\n";
    }
}


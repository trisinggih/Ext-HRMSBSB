<?php
$emp = App\Models\Employee::whereHas("assignments", function($q) { $q->where("salary_cycle_type", "DAILY_PAID_WEEKLY"); })->first();
$comps = App\Models\EmployeeSalaryComponent::where("employee_id", $emp->id)->with("component")->get();
foreach($comps as $c) {
    if($c->component) {
        echo $c->component->code . " -> amount: " . $c->amount . ", calc_type: " . $c->calculation_type . "\n";
    }
}


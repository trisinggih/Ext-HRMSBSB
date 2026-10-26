<?php
$emp = App\Models\Employee::find(139);
$assignment = $emp->assignments()->first();
$period = App\Models\PayrollPeriod::latest()->first();
$calc = app(App\Services\PayrollCalculatorService::class)->calculateForAssignment($emp, $assignment, $period, [
    "working_days" => 30,
    "present_days" => 28,
    "absent_days" => 0,
    "late_minutes" => 0,
    "overtime_hours" => 0
], false);
foreach($calc["_details"] as $d) {
    if ($d["code"] === "gaji_bulanan") {
        echo "Amount: " . $d["amount"] . " | Variables matched: " . json_encode($d["matched_variables"]) . "\n";
    }
}


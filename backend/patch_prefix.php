<?php

function patchController($file, $model, $isPosition = false) {
    $c = file_get_contents($file);

    $prefixLogic = '
        $prefix = "";
';
    if ($isPosition) {
        $prefixLogic .= '
        if (!empty($data["division_id"])) {
            $div = \App\Models\Division::find($data["division_id"]);
            if ($div && $div->code) $prefix = $div->code;
        }
';
    }
    $prefixLogic .= '
        if (!$prefix && !empty($data["department_id"])) {
            $dep = \App\Models\Department::find($data["department_id"]);
            if ($dep && $dep->code) $prefix = $dep->code;
        }
        if (!$prefix && !empty($data["branch_id"])) {
            $branch = \App\Models\Branch::find($data["branch_id"]);
            if ($branch && $branch->code) $prefix = $branch->code;
        }
        if ($prefix && !empty($data["code"]) && !str_starts_with($data["code"], $prefix . "-")) {
            $data["code"] = $prefix . "-" . $data["code"];
        }
';

    // Patch Store
    $storeSearch = "\$".strtolower($model)." = $model::create(\$request->validated());";
    $storeReplace = "\$data = \$request->validated();
$prefixLogic
        \$".strtolower($model)." = $model::create(\$data);";

    if (strpos($c, $storeSearch) !== false) {
        $c = str_replace($storeSearch, $storeReplace, $c);
    } else {
        echo "Store not found for $model\n";
    }

    // Patch Update
    $updateSearch = "\$".strtolower($model)."->update(\$request->validated());";
    $updateReplace = "\$data = \$request->validated();
$prefixLogic
        \$".strtolower($model)."->update(\$data);";

    if (strpos($c, $updateSearch) !== false) {
        $c = str_replace($updateSearch, $updateReplace, $c);
    } else {
        echo "Update not found for $model\n";
    }

    file_put_contents($file, $c);
    echo "Patched $model\n";
}

patchController('d:\Project\HRMS\backend\app\Http\Controllers\Api\v1\Master\DivisionController.php', 'Division', false);
patchController('d:\Project\HRMS\backend\app\Http\Controllers\Api\v1\Master\PositionController.php', 'Position', true);

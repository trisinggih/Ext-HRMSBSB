import os
import re

file_path = 'routes/api.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

import_statement = "use App\Http\Controllers\Api\\v1\Master\EmployeeMutationController;"
if import_statement not in content:
    content = content.replace("use App\Http\Controllers\Api\\v1\Master\EmployeeController;", f"{import_statement}\nuse App\Http\Controllers\Api\\v1\Master\EmployeeController;")

route_group = """
            // Employee Mutations
            Route::apiResource('employee-mutations', EmployeeMutationController::class)->except(['update', 'destroy']);
            Route::get('employee-mutations/{employee_mutation}/pdf', [EmployeeMutationController::class, 'exportPdf'])->name('api.employee-mutations.pdf');
"""

if "employee-mutations" not in content:
    content = content.replace("            // Employee", f"{route_group}\n            // Employee")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Routes injected.")

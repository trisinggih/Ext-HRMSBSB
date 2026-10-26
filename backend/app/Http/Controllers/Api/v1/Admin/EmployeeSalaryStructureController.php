<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSalaryStructure;
use Illuminate\Http\Request;

class EmployeeSalaryStructureController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeSalaryStructure::with(['salaryStructure']);
        
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'salary_structure_id' => 'required|exists:salary_structures,id',
            'effective_date' => 'required|date',
        ]);

        $model = EmployeeSalaryStructure::create($validated);
        return response()->json($model, 201);
    }

    public function show(EmployeeSalaryStructure $employeeSalaryStructure)
    {
        return response()->json($employeeSalaryStructure->load('salaryStructure'));
    }

    public function update(Request $request, EmployeeSalaryStructure $employeeSalaryStructure)
    {
        $validated = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'salary_structure_id' => 'sometimes|exists:salary_structures,id',
            'effective_date' => 'sometimes|date',
        ]);

        $employeeSalaryStructure->update($validated);
        return response()->json($employeeSalaryStructure);
    }

    public function destroy(EmployeeSalaryStructure $employeeSalaryStructure)
    {
        $employeeSalaryStructure->delete();
        return response()->json(null, 204);
    }
}

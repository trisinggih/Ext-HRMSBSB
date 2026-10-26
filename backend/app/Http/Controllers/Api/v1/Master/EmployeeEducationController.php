<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Models\EmployeeEducation;
use Illuminate\Http\Request;

class EmployeeEducationController extends Controller
{
    public function index($employee)
    {
        return response()->json(EmployeeEducation::where('employee_id', $employee)->orderBy('end_year', 'desc')->get());
    }

    public function store(Request $request, $employee)
    {
        $data = $request->validate([
            'level' => 'required|string',
            'institution_name' => 'required|string',
            'major' => 'nullable|string',
            'start_year' => 'nullable|string',
            'end_year' => 'nullable|string',
            'gpa' => 'nullable|numeric'
        ]);
        
        $data['employee_id'] = $employee;
        $edu = EmployeeEducation::create($data);
        return response()->json($edu);
    }

    public function update(Request $request, $employee, $id)
    {
        $edu = EmployeeEducation::where('employee_id', $employee)->findOrFail($id);
        $edu->update($request->all());
        return response()->json($edu);
    }

    public function destroy($employee, $id)
    {
        EmployeeEducation::where('employee_id', $employee)->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

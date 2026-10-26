<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTraining;
use Illuminate\Http\Request;

class EmployeeTrainingController extends Controller
{
    public function index($employee)
    {
        return response()->json(EmployeeTraining::where('employee_id', $employee)->orderBy('end_date', 'desc')->get());
    }

    public function store(Request $request, $employee)
    {
        $data = $request->validate([
            'training_name' => 'required|string',
            'organizer' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'certificate_number' => 'nullable|string'
        ]);
        
        $data['employee_id'] = $employee;
        $trn = EmployeeTraining::create($data);
        return response()->json($trn);
    }

    public function update(Request $request, $employee, $id)
    {
        $trn = EmployeeTraining::where('employee_id', $employee)->findOrFail($id);
        $trn->update($request->all());
        return response()->json($trn);
    }

    public function destroy($employee, $id)
    {
        EmployeeTraining::where('employee_id', $employee)->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

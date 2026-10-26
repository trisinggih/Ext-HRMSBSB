<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollInput;
use Illuminate\Http\Request;

class PayrollInputController extends Controller
{
    public function index()
    {
        return response()->json(PayrollInput::with(['employee:id,name,employee_id', 'component:id,name', 'period:id,name,period'])->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payroll_period_id' => 'required|exists:payroll_periods,id',
            'employee_id' => 'required|exists:employees,id',
            'payroll_component_id' => 'required|exists:payroll_components,id',
            'amount' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'note' => 'nullable|string',
            'source' => 'nullable|string',
        ]);

        $input = PayrollInput::create($validated);
        return response()->json($input, 201);
    }

    public function show(PayrollInput $payrollInput)
    {
        return response()->json($payrollInput);
    }

    public function update(Request $request, PayrollInput $payrollInput)
    {
        $validated = $request->validate([
            'payroll_period_id' => 'sometimes|exists:payroll_periods,id',
            'employee_id' => 'sometimes|exists:employees,id',
            'payroll_component_id' => 'sometimes|exists:payroll_components,id',
            'amount' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'note' => 'nullable|string',
            'source' => 'nullable|string',
        ]);

        $payrollInput->update($validated);
        return response()->json($payrollInput);
    }

    public function destroy(PayrollInput $payrollInput)
    {
        $payrollInput->delete();
        return response()->json(null, 204);
    }
}

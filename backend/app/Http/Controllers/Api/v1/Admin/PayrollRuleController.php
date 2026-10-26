<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollRule;
use Illuminate\Http\Request;

class PayrollRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = PayrollRule::query()
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('code', 'like', '%'.$request->search.'%'))
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payroll_component_id' => 'required|exists:payroll_components,id',
            'code' => 'required|string|unique:payroll_rules',
            'name' => 'required|string',
            'source' => 'required|string',
            'method' => 'required|string',
            'condition' => 'nullable|string',
            'formula' => 'nullable|string',
            'priority' => 'required|integer',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $rule = PayrollRule::create($validated);
        return response()->json($rule, 201);
    }

    public function show(PayrollRule $payrollRule)
    {
        return response()->json($payrollRule);
    }

    public function update(Request $request, PayrollRule $payrollRule)
    {
        $validated = $request->validate([
            'payroll_component_id' => 'sometimes|exists:payroll_components,id',
            'code' => 'sometimes|string|unique:payroll_rules,code,' . $payrollRule->id,
            'name' => 'sometimes|string',
            'source' => 'sometimes|string',
            'method' => 'sometimes|string',
            'condition' => 'nullable|string',
            'formula' => 'nullable|string',
            'priority' => 'sometimes|integer',
            'effective_from' => 'sometimes|date',
            'effective_to' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $payrollRule->update($validated);
        return response()->json($payrollRule);
    }

    public function destroy(PayrollRule $payrollRule)
    {
        $payrollRule->delete();
        return response()->json(null, 204);
    }
}

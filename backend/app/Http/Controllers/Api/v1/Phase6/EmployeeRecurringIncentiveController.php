<?php

namespace App\Http\Controllers\Api\v1\Phase6;

use App\Http\Controllers\Controller;
use App\Models\EmployeeRecurringIncentive;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmployeeRecurringIncentiveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeRecurringIncentive::query()->with('employee:id,employee_id,name');

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar insentif rutin',
            'data' => [
                'items' => $items->items(),
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'payout_day_of_month' => 'nullable|integer|min:1|max:31',
            'is_active' => 'nullable|boolean',
        ]);

        $incentive = EmployeeRecurringIncentive::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Insentif rutin berhasil dibuat',
            'data' => $incentive,
        ], 201);
    }

    public function show(EmployeeRecurringIncentive $employeeRecurringIncentive): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail insentif rutin',
            'data' => $employeeRecurringIncentive,
        ]);
    }

    public function update(Request $request, EmployeeRecurringIncentive $employeeRecurringIncentive): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'payout_day_of_month' => 'nullable|integer|min:1|max:31',
            'is_active' => 'nullable|boolean',
        ]);

        $employeeRecurringIncentive->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Insentif rutin berhasil diperbarui',
            'data' => $employeeRecurringIncentive->fresh(),
        ]);
    }

    public function destroy(EmployeeRecurringIncentive $employeeRecurringIncentive): JsonResponse
    {
        $employeeRecurringIncentive->delete();
        return response()->json([
            'success' => true,
            'message' => 'Insentif rutin berhasil dihapus',
            'data' => null,
        ]);
    }
}

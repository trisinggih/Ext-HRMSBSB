<?php

namespace App\Http\Controllers\Api\v1\Payroll;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLoan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeLoanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $loans = EmployeeLoan::query()
            ->with('employee:id,employee_id,name')
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Daftar pinjaman / kasbon',
            'data' => $loans,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'total_loan' => 'required|numeric|min:0',
            'installment_amount' => 'required|numeric|min:0',
            'status' => 'sometimes|in:active,paid_off',
        ]);

        $validated['remaining_balance'] = $validated['total_loan'];

        $loan = EmployeeLoan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pinjaman berhasil dibuat',
            'data' => $loan->load('employee:id,employee_id,name'),
        ], 201);
    }

    public function show(EmployeeLoan $employeeLoan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail pinjaman',
            'data' => $employeeLoan->load('employee:id,employee_id,name'),
        ]);
    }

    public function update(Request $request, EmployeeLoan $employeeLoan): JsonResponse
    {
        $validated = $request->validate([
            'total_loan' => 'sometimes|numeric|min:0',
            'remaining_balance' => 'sometimes|numeric|min:0',
            'installment_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,paid_off',
        ]);

        $employeeLoan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pinjaman berhasil diperbarui',
            'data' => $employeeLoan->fresh('employee:id,employee_id,name'),
        ]);
    }

    public function destroy(EmployeeLoan $employeeLoan): JsonResponse
    {
        $employeeLoan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pinjaman berhasil dihapus',
            'data' => null,
        ]);
    }
}

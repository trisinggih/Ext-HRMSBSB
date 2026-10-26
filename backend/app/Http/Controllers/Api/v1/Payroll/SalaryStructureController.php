<?php

namespace App\Http\Controllers\Api\v1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreSalaryStructureRequest;
use App\Http\Requests\Payroll\StoreSalaryStructureItemRequest;
use App\Http\Resources\Payroll\EmployeeSalaryComponentResource;
use App\Models\EmployeeSalaryComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    /**
     * List struktur gaji, filter optional employee_id.
     */
    public function index(Request $request): JsonResponse
    {
        $items = EmployeeSalaryComponent::query()
            ->with(['employee:id,employee_id,name', 'component'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('component', fn($c) => $c->where('category', $request->category)))
            ->orderBy('updated_at', 'desc')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Daftar struktur gaji',
            'data' => EmployeeSalaryComponentResource::collection($items)->response()->getData(true),
        ]);
    }

    /**
     * Assign TRAMPIL: array items komponen ke satu employee (batch).
     */
    public function store(StoreSalaryStructureRequest $request): JsonResponse
    {
        $employeeId = $request->integer('employee_id');
        $created = [];

        foreach ($request->input('items', []) as $item) {
            $data = [
                'employee_id' => $employeeId,
                'payroll_component_id' => $item['payroll_component_id'],
                'amount' => $item['amount'] ?? 0,
                'percentage' => $item['percentage'] ?? null,
                'effective_from' => $item['effective_from'],
                'is_active' => $item['is_active'] ?? true,
            ];
            $created[] = EmployeeSalaryComponent::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'payroll_component_id' => $item['payroll_component_id'],
                ],
                $data
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Struktur gaji berhasil disimpan',
            'data' => EmployeeSalaryComponentResource::collection($created)->response()->getData(true),
        ], 201);
    }

    /**
     * Assign SATU komponen ke satu employee (route: /salary-structures/{employee}).
     */
    public function storeItem(StoreSalaryStructureItemRequest $request, int $employee): JsonResponse
    {
        $item = EmployeeSalaryComponent::updateOrCreate(
            [
                'employee_id' => $employee,
                'payroll_component_id' => $request->payroll_component_id,
            ],
            [
                'amount' => $request->amount ?? 0,
                'percentage' => $request->percentage ?? null,
                'effective_from' => $request->effective_from,
                'is_active' => $request->is_active ?? true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Komponen struktur berhasil disimpan',
            'data' => new EmployeeSalaryComponentResource($item->load('component')),
        ], 201);
    }

    public function show(EmployeeSalaryComponent $salaryStructure): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail struktur gaji',
            'data' => new EmployeeSalaryComponentResource($salaryStructure->load(['employee:id,employee_id,name', 'component'])),
        ]);
    }

    public function update(StoreSalaryStructureItemRequest $request, EmployeeSalaryComponent $salaryStructure): JsonResponse
    {
        $salaryStructure->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Struktur gaji berhasil diperbarui',
            'data' => new EmployeeSalaryComponentResource($salaryStructure->fresh('component')),
        ]);
    }

    public function destroy(EmployeeSalaryComponent $salaryStructure): JsonResponse
    {
        $salaryStructure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Struktur gaji berhasil dihapus',
            'data' => null,
        ]);
    }
}

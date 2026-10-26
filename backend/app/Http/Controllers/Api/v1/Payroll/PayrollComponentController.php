<?php

namespace App\Http\Controllers\Api\v1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollComponentRequest;
use App\Http\Requests\Payroll\UpdatePayrollComponentRequest;
use App\Http\Resources\Payroll\PayrollComponentResource;
use App\Models\PayrollComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollComponentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $components = PayrollComponent::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('category'), function ($q) use ($request) {
                $q->whereJsonContains('category', $request->category);
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Daftar komponen payroll',
            'data' => PayrollComponentResource::collection($components)->response()->getData(true),
        ]);
    }

    public function store(StorePayrollComponentRequest $request): JsonResponse
    {
        $component = PayrollComponent::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Komponen payroll berhasil dibuat',
            'data' => new PayrollComponentResource($component),
        ], 201);
    }

    public function show(PayrollComponent $component): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail komponen payroll',
            'data' => new PayrollComponentResource($component),
        ]);
    }

    public function update(UpdatePayrollComponentRequest $request, PayrollComponent $component): JsonResponse
    {
        $component->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Komponen payroll berhasil diperbarui',
            'data' => new PayrollComponentResource($component->fresh()),
        ]);
    }

    public function destroy(PayrollComponent $component): JsonResponse
    {
        $component->delete();

        return response()->json([
            'success' => true,
            'message' => 'Komponen payroll berhasil dihapus',
            'data' => null,
        ]);
    }
}

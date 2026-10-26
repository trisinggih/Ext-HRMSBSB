<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StorePositionRequest;
use App\Http\Requests\Master\UpdatePositionRequest;
use App\Http\Resources\Master\PositionResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $positions = Position::query()
            ->when(!$request->boolean('include_executives') && !$request->boolean('for_payroll'), fn ($q) => $q->withoutExecutives())
            ->with(['department', 'division', 'parent', 'payrollComponents.component'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('division_id'), fn ($q) => $q->where('division_id', $request->division_id))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sort_by'), fn ($q) => $q->orderBy($request->sort_by, $request->get('sort_dir', 'asc')), fn ($q) => $q->orderBy('name'))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar position',
            'data' => PositionResource::collection($positions)->response()->getData(true),
        ]);
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $prefix = "";

        if (!empty($data["division_id"])) {
            $div = \App\Models\Division::find($data["division_id"]);
            if ($div && $div->code) $prefix = $div->code;
        }

        if (!$prefix && !empty($data["department_id"])) {
            $dep = \App\Models\Department::find($data["department_id"]);
            if ($dep && $dep->code) $prefix = $dep->code;
        }
        if (!$prefix && !empty($data["branch_id"])) {
            $branch = \App\Models\Branch::find($data["branch_id"]);
            if ($branch && $branch->code) $prefix = $branch->code;
        }
        if ($prefix && !empty($data["code"]) && !str_starts_with($data["code"], $prefix . "-")) {
            $data["code"] = $prefix . "-" . $data["code"];
        }

        $position = Position::create($data);
        
        if ($request->has('payroll_components') && is_array($request->payroll_components)) {
            foreach ($request->payroll_components as $comp) {
                \App\Models\PositionPayrollComponent::create([
                    'position_id' => $position->id,
                    'payroll_component_id' => $comp['payroll_component_id'],
                    'fixed_amount' => $comp['fixed_amount'] ?? 0
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Position berhasil dibuat',
            'data' => new PositionResource($position->load(['company', 'division', 'branch', 'parent', 'payrollComponents.component'])),
        ], 201);
    }

    public function show(Position $position): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail position',
            'data' => new PositionResource($position->load(['company', 'division', 'branch', 'parent', 'payrollComponents.component'])),
        ]);
    }

    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $data = $request->validated();

        $prefix = "";

        if (!empty($data["division_id"])) {
            $div = \App\Models\Division::find($data["division_id"]);
            if ($div && $div->code) $prefix = $div->code;
        }

        if (!$prefix && !empty($data["department_id"])) {
            $dep = \App\Models\Department::find($data["department_id"]);
            if ($dep && $dep->code) $prefix = $dep->code;
        }
        if (!$prefix && !empty($data["branch_id"])) {
            $branch = \App\Models\Branch::find($data["branch_id"]);
            if ($branch && $branch->code) $prefix = $branch->code;
        }
        if ($prefix && !empty($data["code"]) && !str_starts_with($data["code"], $prefix . "-")) {
            $data["code"] = $prefix . "-" . $data["code"];
        }

        $position->update($data);
        
        if ($request->has('payroll_components') && is_array($request->payroll_components)) {
            $position->payrollComponents()->delete();
            foreach ($request->payroll_components as $comp) {
                \App\Models\PositionPayrollComponent::create([
                    'position_id' => $position->id,
                    'payroll_component_id' => $comp['payroll_component_id'],
                    'fixed_amount' => $comp['fixed_amount'] ?? 0
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Position berhasil diperbarui',
            'data' => new PositionResource($position->load(['company', 'division', 'branch', 'parent', 'payrollComponents.component'])),
        ]);
    }

    public function destroy(Position $position): JsonResponse
    {
        if (\App\Models\Department::where('manager_position_id', $position->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Posisi ini adalah Manager Departemen dan tidak dapat dihapus kecuali Departemen-nya dihapus.',
            ], 422);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Position berhasil dihapus',
            'data' => null,
        ]);
    }
}



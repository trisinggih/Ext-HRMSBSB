<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDivisionRequest;
use App\Http\Requests\Master\UpdateDivisionRequest;
use App\Http\Resources\Master\DivisionResource;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $divisions = Division::query()
            ->with(['department'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sort_by'), fn ($q) => $q->orderBy($request->sort_by, $request->get('sort_dir', 'asc')), fn ($q) => $q->orderBy('name'))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar division',
            'data' => DivisionResource::collection($divisions)->response()->getData(true),
        ]);
    }

    public function store(StoreDivisionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $prefix = "";

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

        $division = Division::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Division berhasil dibuat',
            'data' => new DivisionResource($division->load('department')),
        ], 201);
    }

    public function show(Division $division): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail division',
            'data' => new DivisionResource($division->load(['department', 'positions'])),
        ]);
    }

    public function update(UpdateDivisionRequest $request, Division $division): JsonResponse
    {
        $data = $request->validated();

        $prefix = "";

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

        $division->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Division berhasil diperbarui',
            'data' => new DivisionResource($division->load('department')),
        ]);
    }

    public function destroy(Division $division): JsonResponse
    {
        $division->delete();

        return response()->json([
            'success' => true,
            'message' => 'Division berhasil dihapus',
            'data' => null,
        ]);
    }
}


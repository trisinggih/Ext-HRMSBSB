<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreBranchRequest;
use App\Http\Requests\Master\UpdateBranchRequest;
use App\Http\Resources\Master\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $branches = Branch::query()
            ->with('company')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sort_by'), fn ($q) => $q->orderBy($request->sort_by, $request->get('sort_dir', 'asc')), fn ($q) => $q->orderBy('name'))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar branch',
            'data' => BranchResource::collection($branches)->response()->getData(true),
        ]);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('branch-logos', 'public');
        }

        $branch = Branch::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Branch berhasil dibuat',
            'data' => new BranchResource($branch->load('company')),
        ], 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail branch',
            'data' => new BranchResource($branch->load(['company', 'departments'])),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
                Storage::disk('public')->delete($branch->logo);
            }
            $data['logo'] = $request->file('logo')->store('branch-logos', 'public');
        } elseif ($request->exists('logo') && (empty($request->input('logo')) || $request->input('logo') === 'null')) {
            if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
                Storage::disk('public')->delete($branch->logo);
            }
            $data['logo'] = null;
        } else {
            unset($data['logo']);
        }

        $branch->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Branch berhasil diperbarui',
            'data' => new BranchResource($branch->load('company')),
        ]);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
            Storage::disk('public')->delete($branch->logo);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch berhasil dihapus',
            'data' => null,
        ]);
    }
}

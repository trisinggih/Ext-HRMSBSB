<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = CompanyGroup::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) =>
                $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sort_by'), fn ($q) => $q->orderBy($request->sort_by, $request->get('sort_dir', 'asc')), fn ($q) => $q->orderBy('name'))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar company group',
            'data' => $groups,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|max:10240',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('group-logos', 'public');
        }

        $group = CompanyGroup::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Company Group berhasil dibuat',
            'data' => $group,
        ], 201);
    }

    public function show(CompanyGroup $companyGroup): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail company group',
            'data' => $companyGroup->load('companies'),
        ]);
    }

    public function update(Request $request, CompanyGroup $companyGroup): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|max:10240',
            'favicon' => 'nullable|image|max:10240',
            'is_active' => 'nullable|boolean',
        ]);
        
        if ($request->hasFile('logo')) {
            if ($companyGroup->logo) {
                Storage::disk('public')->delete($companyGroup->logo);
            }
            $data['logo'] = $request->file('logo')->store('group-logos', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($companyGroup->favicon) {
                Storage::disk('public')->delete($companyGroup->favicon);
            }
            $data['favicon'] = $request->file('favicon')->store('group-favicons', 'public');
        }

        $companyGroup->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Company Group berhasil diperbarui',
            'data' => $companyGroup,
        ]);
    }

    public function destroy(CompanyGroup $companyGroup): JsonResponse
    {
        $companyGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company Group berhasil dihapus',
            'data' => null,
        ]);
    }
}


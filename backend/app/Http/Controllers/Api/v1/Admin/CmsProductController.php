<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CMS — Katalog Produk (Admin CRUD, ability:admin).
 */
class CmsProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CmsProduct::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%"))
            ->when($request->filled('category'), fn ($q) =>
                $q->where('category', $request->category))
            ->orderBy('id');

        $perPage = $request->integer('per_page', 20);

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk',
            'data'    => $query->paginate($perPage),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'category'         => 'required|string|max:100',
            'categoryLabel'    => 'nullable|string|max:255',
            'badge'            => 'nullable|string|max:100',
            'highlight'        => 'nullable|string|max:255',
            'specLabel'        => 'nullable|string|max:255',
            'specValue'        => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'images'           => 'nullable|array',
            'images.*'         => 'nullable|string|url',
            'specs'            => 'nullable|array',
            'compatibility'    => 'nullable|array',
            'marketplaceLinks' => 'nullable|array',
            'is_active'        => 'boolean',
        ]);

        $product = CmsProduct::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat.',
            'data'    => $product,
        ], 201);
    }

    public function show(CmsProduct $cmsProduct): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail produk',
            'data'    => $cmsProduct,
        ]);
    }

    public function update(Request $request, CmsProduct $cmsProduct): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'category'         => 'sometimes|required|string|max:100',
            'categoryLabel'    => 'nullable|string|max:255',
            'badge'            => 'nullable|string|max:100',
            'highlight'        => 'nullable|string|max:255',
            'specLabel'        => 'nullable|string|max:255',
            'specValue'        => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'images'           => 'nullable|array',
            'images.*'         => 'nullable|string',
            'specs'            => 'nullable|array',
            'compatibility'    => 'nullable|array',
            'marketplaceLinks' => 'nullable|array',
            'is_active'        => 'boolean',
        ]);

        $cmsProduct->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk diperbarui.',
            'data'    => $cmsProduct->fresh(),
        ]);
    }

    public function destroy(CmsProduct $cmsProduct): JsonResponse
    {
        $cmsProduct->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk dihapus.',
            'data'    => null,
        ]);
    }
}

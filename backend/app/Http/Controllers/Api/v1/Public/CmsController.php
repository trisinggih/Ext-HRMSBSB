<?php

namespace App\Http\Controllers\Api\v1\Public;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsProduct;
use App\Models\Cms\CmsSetting;
use Illuminate\Http\JsonResponse;

/**
 * CMS — Company Profile (Public API, tanpa auth).
 * GET /api/v1/public/cms/settings  → semua setting aktif sebagai key-value
 * GET /api/v1/public/cms/products  → semua produk aktif
 */
class CmsController extends Controller
{
    public function getSettings(): JsonResponse
    {
        $settings = CmsSetting::where('is_active', true)
            ->orderBy('key')
            ->get()
            ->pluck('value', 'key');

        return response()->json([
            'status' => 'success',
            'data'   => $settings,
        ]);
    }

    public function getProducts(): JsonResponse
    {
        $products = CmsProduct::where('is_active', true)
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $products,
        ]);
    }
}

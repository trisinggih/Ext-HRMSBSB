<?php

namespace App\Http\Controllers\Api\v1\Cms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\CmsPageResource;
use App\Http\Resources\Cms\CmsSectionResource;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsSection;
use App\Models\Cms\CmsSetting;
use Illuminate\Http\JsonResponse;

/**
 * CMS — Company Profile (Endpoints PUBLIC utk landing/website).
 */
class PublicCmsController extends Controller
{
    /** GET /cms/site — semua setting aktif digabung dalam 1 objek + seksi publik */
    public function site(): JsonResponse
    {
        $settings = CmsSetting::where('is_active', true)->get();

        $settingsObject = [];
        foreach ($settings as $setting) {
            $settingsObject[$setting->key] = $setting->value;
        }

        // seksi publik yang aktif (tidak terikat halaman) utk landing
        $sections = CmsSection::where('is_active', true)
            ->whereNull('page_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan situs',
            'data' => [
                'settings' => $settingsObject,
                'sections' => CmsSectionResource::collection($sections),
            ],
        ]);
    }

    /** GET /cms/pages/{slug} — halaman publik */
    public function page(string $slug): JsonResponse
    {
        $page = CmsPage::with(['sections' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->where('slug', $slug)
            ->where('status', true)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Halaman',
            'data' => new CmsPageResource($page),
        ]);
    }

    /** GET /cms/sections/{key} — seksi publik per key */
    public function section(string $key): JsonResponse
    {
        $section = CmsSection::where('key', $key)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Seksi',
            'data' => new CmsSectionResource($section),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\v1\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreCmsPageRequest;
use App\Http\Requests\Cms\StoreCmsSectionRequest;
use App\Http\Requests\Cms\UpdateCmsPageRequest;
use App\Http\Requests\Cms\UpdateCmsSectionRequest;
use App\Http\Requests\Cms\UpsertCmsSettingRequest;
use App\Http\Resources\Cms\CmsMediaResource;
use App\Http\Resources\Cms\CmsPageResource;
use App\Http\Resources\Cms\CmsSectionResource;
use App\Http\Resources\Cms\CmsSettingResource;
use App\Models\Cms\CmsMedia;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsSection;
use App\Models\Cms\CmsSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * CMS — Company Profile (Admin CRUD, ability:admin).
 */
class AdminCmsController extends Controller
{
    // ===================== SETTINGS (upsert by key) =====================

    public function settings(Request $request): JsonResponse
    {
        $query = CmsSetting::query()
            ->when($request->filled('group'), fn ($q) => $q->where('group', $request->group))
            ->orderBy('key');

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengaturan',
            'data' => CmsSettingResource::collection($query->paginate($request->integer('per_page', 50)))->response()->getData(true),
        ]);
    }

    public function upsertSetting(UpsertCmsSettingRequest $request): JsonResponse
    {
        $data = $request->validated();

        $setting = CmsSetting::updateOrCreate(
            ['key' => $data['key']],
            [
                'value' => $data['value'] ?? null,
                'group' => $data['group'] ?? 'general',
                'is_active' => $data['is_active'] ?? true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan disimpan.',
            'data' => new CmsSettingResource($setting),
        ], 200);
    }

    public function showSetting(string $key): JsonResponse
    {
        $setting = CmsSetting::where('key', $key)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan',
            'data' => new CmsSettingResource($setting),
        ]);
    }

    public function destroySetting(string $key): JsonResponse
    {
        $setting = CmsSetting::where('key', $key)->firstOrFail();
        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan dihapus.',
            'data' => null,
        ]);
    }

    // ===================== PAGES =====================

    public function pages(Request $request): JsonResponse
    {
        $query = CmsPage::query()
            ->with('sections')
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%"))
            ->orderByDesc('id');

        return response()->json([
            'success' => true,
            'message' => 'Daftar halaman',
            'data' => CmsPageResource::collection($query->paginate($request->integer('per_page', 15)))->response()->getData(true),
        ]);
    }

    public function storePage(StoreCmsPageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        $page = CmsPage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Halaman dibuat.',
            'data' => new CmsPageResource($page),
        ], 201);
    }

    public function showPage(CmsPage $page): JsonResponse
    {
        $page->load('sections');

        return response()->json([
            'success' => true,
            'message' => 'Detail halaman',
            'data' => new CmsPageResource($page),
        ]);
    }

    public function updatePage(UpdateCmsPageRequest $request, CmsPage $page): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['slug'])) {
            $data['slug'] = $data['slug'] ?: Str::slug($data['title'] ?? $page->title);
        }

        $page->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Halaman diperbarui.',
            'data' => new CmsPageResource($page->fresh()->load('sections')),
        ]);
    }

    public function destroyPage(CmsPage $page): JsonResponse
    {
        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Halaman dihapus.',
            'data' => null,
        ]);
    }

    // ===================== SECTIONS =====================

    public function sections(Request $request): JsonResponse
    {
        $query = CmsSection::query()
            ->with('page')
            ->when($request->filled('page_id'), fn ($q) => $q->where('page_id', $request->page_id))
            ->when($request->filled('key'), fn ($q) => $q->where('key', $request->key))
            ->orderBy('sort_order');

        return response()->json([
            'success' => true,
            'message' => 'Daftar seksi',
            'data' => CmsSectionResource::collection($query->paginate($request->integer('per_page', 15)))->response()->getData(true),
        ]);
    }

    public function storeSection(StoreCmsSectionRequest $request): JsonResponse
    {
        $section = CmsSection::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Seksi dibuat.',
            'data' => new CmsSectionResource($section),
        ], 201);
    }

    public function showSection(CmsSection $section): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail seksi',
            'data' => new CmsSectionResource($section),
        ]);
    }

    public function updateSection(UpdateCmsSectionRequest $request, CmsSection $section): JsonResponse
    {
        $section->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Seksi diperbarui.',
            'data' => new CmsSectionResource($section->fresh('page')),
        ]);
    }

    public function destroySection(CmsSection $section): JsonResponse
    {
        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seksi dihapus.',
            'data' => null,
        ]);
    }

    // ===================== MEDIA (opsional) =====================

    public function media(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar media',
            'data' => CmsMediaResource::collection(CmsMedia::query()->latest()->paginate($request->integer('per_page', 15)))->response()->getData(true),
        ]);
    }

    public function storeMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,webp,svg,pdf,mp4|max:8192',
        ]);

        $file = $request->file('file');
        $path = $file->store('cms/media', 'public');

        $media = CmsMedia::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Media diunggah.',
            'data' => new CmsMediaResource($media),
        ], 201);
    }

    public function destroyMedia(CmsMedia $media): JsonResponse
    {
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media dihapus.',
            'data' => null,
        ]);
    }
}

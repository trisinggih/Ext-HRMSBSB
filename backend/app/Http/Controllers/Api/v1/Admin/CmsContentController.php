<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class CmsContentController extends Controller
{
    // Hero Section
    public function getHero()
    {
        $keys = [
            'hero_badge',
            'hero_title_line1',
            'hero_title_line2',
            'hero_subtitle',
            'hero_cta_catalog',
            'hero_image_url'
        ];
        $hero = DB::table('cms_settings')->whereIn('key', $keys)->pluck('value', 'key');
        return response()->json(['data' => $hero]);
    }

    public function updateHero(Request $request)
    {
        $data = $request->only([
            'hero_badge',
            'hero_title_line1',
            'hero_title_line2',
            'hero_subtitle',
            'hero_cta_catalog',
            'hero_image_url'
        ]);

        foreach ($data as $key => $value) {
            DB::table('cms_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'hero'],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Banner Utama berhasil diperbarui']);
    }

    // About Us
    public function getAbout()
    {
        $about = DB::table('cms_sections')->where('key', 'about_us')->first();
        return response()->json(['data' => $about]);
    }

    public function updateAbout(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string',
            'content' => 'nullable|string',
            'image_url' => 'nullable|string'
        ]);

        DB::table('cms_sections')->updateOrInsert(
            ['key' => 'about_us'],
            [
                'title' => $request->title ?? 'Tentang Kami',
                'content' => $request->content,
                'image_url' => $request->image_url,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())')
            ]
        );

        return response()->json(['message' => 'Tentang Kami berhasil diperbarui']);
    }

    // Advantages
    public function getAdvantages()
    {
        $advantages = DB::table('cms_sections')->where('key', 'advantages')->orderBy('sort_order')->get();
        return response()->json(['data' => $advantages]);
    }

    public function storeAdvantage(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
            'image_url' => 'nullable|string'
        ]);

        $id = DB::table('cms_sections')->insertGetId([
            'key' => 'advantages',
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $request->image_url,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Keunggulan berhasil ditambahkan', 'id' => $id]);
    }

    public function updateAdvantage(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
            'image_url' => 'nullable|string'
        ]);

        DB::table('cms_sections')->where('id', $id)->where('key', 'advantages')->update([
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $request->image_url,
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Keunggulan berhasil diperbarui']);
    }

    public function destroyAdvantage($id)
    {
        DB::table('cms_sections')->where('id', $id)->where('key', 'advantages')->delete();
        return response()->json(['message' => 'Keunggulan berhasil dihapus']);
    }

    // Marketplaces
    public function getMarketplaces()
    {
        $marketplaces = DB::table('cms_settings')->where('group', 'marketplaces')->pluck('value', 'key');
        return response()->json(['data' => $marketplaces]);
    }

    public function updateMarketplaces(Request $request)
    {
        $data = $request->all();
        foreach ($data as $key => $value) {
            DB::table('cms_settings')->updateOrInsert(
                ['key' => $key, 'group' => 'marketplaces'],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Marketplace berhasil diperbarui']);
    }

    // Contact
    public function getContact()
    {
        $company = Company::first();
        return response()->json(['data' => $company]);
    }

    public function updateContact(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string',
            'email' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string',
        ]);

        $company = Company::first();
        if ($company) {
            $company->update($request->only(['phone', 'email', 'address', 'city', 'province', 'postal_code']));
        }

        return response()->json(['message' => 'Informasi Kontak berhasil diperbarui']);
    }
}

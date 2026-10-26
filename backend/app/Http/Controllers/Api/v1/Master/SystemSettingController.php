<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = SystemSetting::all();
        // Return as key-value pairs for easy frontend binding
        $data = $settings->pluck('value', 'key')->toArray();
        
        return response()->json([
            'success' => true,
            'message' => 'System settings',
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nip_format' => 'required|string',
            'nip_counter' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil disimpan',
            'data' => $data
        ]);
    }
}

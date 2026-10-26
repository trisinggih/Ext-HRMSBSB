<?php

namespace App\Http\Controllers\Api\v1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePtkpSettingRequest;
use App\Http\Resources\Payroll\PtkpSettingResource;
use App\Models\PtkpSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PtkpSettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = PtkpSetting::query()
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('status')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Daftar setting PTKP',
            'data' => PtkpSettingResource::collection($items)->response()->getData(true),
        ]);
    }

    public function store(StorePtkpSettingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] = strtoupper($data['status']);

        if (PtkpSetting::where('status', $data['status'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Status PTKP "'.$data['status'].'" sudah ada. Gunakan update untuk mengubah nilainya.',
                'data' => null,
            ], 422);
        }

        $item = PtkpSetting::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Setting PTKP berhasil dibuat',
            'data' => new PtkpSettingResource($item),
        ], 201);
    }

    public function show(PtkpSetting $ptkpSetting): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail setting PTKP',
            'data' => new PtkpSettingResource($ptkpSetting),
        ]);
    }

    public function update(Request $request, PtkpSetting $ptkpSetting): JsonResponse
    {
        $ptkpSetting->update($request->only(['status', 'name', 'ptkp_amount', 'effective_from', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Setting PTKP berhasil diperbarui',
            'data' => new PtkpSettingResource($ptkpSetting->fresh()),
        ]);
    }

    public function destroy(PtkpSetting $ptkpSetting): JsonResponse
    {
        $ptkpSetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting PTKP berhasil dihapus',
            'data' => null,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\v1\Kpi;

use App\Http\Controllers\Controller;
use App\Models\KpiRatingScale;
use App\Models\KpiRatingLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiRatingScaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = KpiRatingScale::query()
            ->with('levels')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'levels' => 'sometimes|array',
            'levels.*.label' => 'required|string|max:100',
            'levels.*.score' => 'required|numeric',
            'levels.*.description' => 'nullable|string',
            'levels.*.sort_order' => 'sometimes|integer',
        ]);

        $scale = KpiRatingScale::create($data);

        if (!empty($data['levels'])) {
            foreach ($data['levels'] as $i => $level) {
                $scale->levels()->create(array_merge($level, ['sort_order' => $i]));
            }
        }

        return response()->json(['success' => true, 'data' => $scale->load('levels')], 201);
    }

    public function show(KpiRatingScale $kpiRatingScale): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $kpiRatingScale->load('levels')]);
    }

    public function update(Request $request, KpiRatingScale $kpiRatingScale): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'levels' => 'sometimes|array',
            'levels.*.id' => 'sometimes|exists:kpi_rating_levels,id',
            'levels.*.label' => 'required|string|max:100',
            'levels.*.score' => 'required|numeric',
            'levels.*.description' => 'nullable|string',
            'levels.*.sort_order' => 'sometimes|integer',
        ]);

        $kpiRatingScale->update($data);

        if (isset($data['levels'])) {
            // Sync levels: hapus lama, insert baru
            $kpiRatingScale->levels()->delete();
            foreach ($data['levels'] as $i => $level) {
                $kpiRatingScale->levels()->create(array_merge($level, ['sort_order' => $i]));
            }
        }

        return response()->json(['success' => true, 'data' => $kpiRatingScale->load('levels')]);
    }

    public function destroy(KpiRatingScale $kpiRatingScale): JsonResponse
    {
        $kpiRatingScale->delete();
        return response()->json(['success' => true, 'message' => 'Skala penilaian berhasil dihapus']);
    }
}

<?php

namespace App\Http\Controllers\Api\v1\Kpi;

use App\Http\Controllers\Controller;
use App\Models\KpiIndicator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiIndicatorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = KpiIndicator::query()
            ->with(['ratingScale', 'templateIndicators.template.position.department', 'templateIndicators.template.position.division', 'templateIndicators.template.division'])
            ->when($request->filled('search'), fn ($q) => $q->where(function($sub) use ($request) {
                $sub->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%")
                    ->orWhere('calculation_method', 'like', "%{$request->search}%")
                    ->orWhere('category', 'like', "%{$request->search}%");
            }))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:kpi_indicators,code',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'type' => 'required|in:individual,division,both',
            'measurement_unit' => 'nullable|string|max:50',
            'frequency' => 'nullable|string|max:50',
            'calculation_method' => 'nullable|string',
            'scoring_points' => 'nullable',
            'rating_scale_id' => 'nullable|exists:kpi_rating_scales,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $item = KpiIndicator::create($data);
        return response()->json(['success' => true, 'data' => $item->load('ratingScale')], 201);
    }

    public function show(KpiIndicator $kpiIndicator): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $kpiIndicator->load(['ratingScale', 'templateIndicators.template.position.department', 'templateIndicators.template.position.division', 'templateIndicators.template.division'])]);
    }

    public function update(Request $request, KpiIndicator $kpiIndicator): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50|unique:kpi_indicators,code,' . $kpiIndicator->id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'type' => 'sometimes|required|in:individual,division,both',
            'measurement_unit' => 'nullable|string|max:50',
            'frequency' => 'nullable|string|max:50',
            'calculation_method' => 'nullable|string',
            'scoring_points' => 'nullable',
            'rating_scale_id' => 'nullable|exists:kpi_rating_scales,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $kpiIndicator->update($data);
        return response()->json(['success' => true, 'data' => $kpiIndicator->load('ratingScale')]);
    }

    public function destroy(KpiIndicator $kpiIndicator): JsonResponse
    {
        $kpiIndicator->delete();
        return response()->json(['success' => true, 'message' => 'Indikator berhasil dihapus']);
    }
}

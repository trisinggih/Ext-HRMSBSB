<?php

namespace App\Http\Controllers\Api\v1\Kpi;

use App\Http\Controllers\Controller;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateIndicator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = KpiTemplate::query()
            ->with(['position.department', 'position.division', 'division', 'ratingScale', 'templateIndicators.indicator'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->filled('scope'), fn ($q) => $q->where('scope', $request->scope))
            ->when($request->filled('position_id'), fn ($q) => $q->where('position_id', $request->position_id))
            ->when($request->filled('division_id'), fn ($q) => $q->where('division_id', $request->division_id))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope' => 'required|in:position,division',
            'position_id' => 'nullable|exists:positions,id',
            'division_id' => 'nullable|exists:divisions,id',
            'rating_scale_id' => 'nullable|exists:kpi_rating_scales,id',
            'is_active' => 'sometimes|boolean',
            'indicators' => 'sometimes|array',
            'indicators.*.indicator_id' => 'required|exists:kpi_indicators,id',
            'indicators.*.weight' => 'required|numeric|min:0',
            'indicators.*.target' => 'nullable|string',
            'indicators.*.frequency' => 'nullable|string',
            'indicators.*.calculation_method' => 'nullable|string',
            'indicators.*.scoring_points' => 'nullable',
        ]);

        $template = KpiTemplate::create($data);

        if (!empty($data['indicators'])) {
            foreach ($data['indicators'] as $i => $item) {
                $template->templateIndicators()->create(array_merge($item, ['sort_order' => $i]));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $template->load(['position.department', 'position.division', 'division', 'ratingScale', 'templateIndicators.indicator']),
        ], 201);
    }

    public function show(KpiTemplate $kpiTemplate): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $kpiTemplate->load(['position.department', 'position.division', 'division', 'ratingScale', 'templateIndicators.indicator']),
        ]);
    }

    public function update(Request $request, KpiTemplate $kpiTemplate): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'scope' => 'sometimes|required|in:position,division',
            'position_id' => 'nullable|exists:positions,id',
            'division_id' => 'nullable|exists:divisions,id',
            'rating_scale_id' => 'nullable|exists:kpi_rating_scales,id',
            'is_active' => 'sometimes|boolean',
            'indicators' => 'sometimes|array',
            'indicators.*.indicator_id' => 'required|exists:kpi_indicators,id',
            'indicators.*.weight' => 'required|numeric|min:0',
            'indicators.*.target' => 'nullable|string',
            'indicators.*.frequency' => 'nullable|string',
            'indicators.*.calculation_method' => 'nullable|string',
            'indicators.*.scoring_points' => 'nullable',
        ]);

        $kpiTemplate->update($data);

        if (isset($data['indicators'])) {
            $kpiTemplate->templateIndicators()->delete();
            foreach ($data['indicators'] as $i => $item) {
                $kpiTemplate->templateIndicators()->create(array_merge($item, ['sort_order' => $i]));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $kpiTemplate->load(['position.department', 'position.division', 'division', 'ratingScale', 'templateIndicators.indicator']),
        ]);
    }

    public function destroy(KpiTemplate $kpiTemplate): JsonResponse
    {
        $kpiTemplate->delete();
        return response()->json(['success' => true, 'message' => 'Template KPI berhasil dihapus']);
    }
}

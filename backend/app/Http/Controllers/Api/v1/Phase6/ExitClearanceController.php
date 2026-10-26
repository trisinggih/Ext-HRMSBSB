<?php

namespace App\Http\Controllers\Api\v1\Phase6;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExitClearance\CheckClearanceItemRequest;
use App\Http\Requests\ExitClearance\StoreClearanceItemRequest;
use App\Http\Resources\ExitClearance\ExitClearanceResource;
use App\Models\EmployeeTermination;
use App\Models\TerminationClearance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExitClearanceController extends Controller
{
    /**
     * GET /admin /exit-clearances
     * Daftar item clearance (filter employee_id / termination_id).
     */
    public function index(Request $request): JsonResponse
    {
        $query = TerminationClearance::query()
            ->with(['termination.employee:id,employee_id,name']);

        if ($request->has('termination_id')) {
            $query->where('termination_id', $request->integer('termination_id'));
        }

        if ($request->has('employee_id')) {
            $employeeId = $request->integer('employee_id');
            $query->whereHas('termination', fn ($q) => $q->where('employee_id', $employeeId));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar exit clearance',
            'data' => [
                'items' => ExitClearanceResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * POST /admin /exit-clearances/{termination}/items
     * Tambah item checklist pada terminasi (termination_id).
     */
    public function storeItem(StoreClearanceItemRequest $request, EmployeeTermination $termination): JsonResponse
    {
        $item = TerminationClearance::create([
            'termination_id' => $termination->id,
            'item' => $request->input('item'),
            'note' => $request->input('note'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item clearance berhasil ditambahkan',
            'data' => new ExitClearanceResource($item->load('termination.employee:id,employee_id,name')),
        ], 201);
    }

    /**
     * PUT /admin /exit-clearances/{termination}/items/{item}/check
     * Centang item (checked / uncheck).
     */
    public function checkItem(
        CheckClearanceItemRequest $request,
        EmployeeTermination $termination,
        TerminationClearance $item
    ): JsonResponse {
        $this->ensureBelongsTo($item, $termination->id);

        $checked = $request->boolean('checked', true);

        $item->update([
            'is_returned' => $checked,
            'returned_at' => $checked ? now() : null,
            'note' => $request->input('note', $item->note),
        ]);

        return response()->json([
            'success' => true,
            'message' => $checked ? 'Item clearance ditandai selesai' : 'Item clearance dibatalkan',
            'data' => new ExitClearanceResource($item->fresh('termination.employee:id,employee_id,name')),
        ]);
    }

    /** GET /admin /exit-clearances/{clearance} */
    public function show(TerminationClearance $clearance): JsonResponse
    {
        $clearance->load('termination.employee:id,employee_id,name');

        return response()->json([
            'success' => true,
            'message' => 'Detail exit clearance',
            'data' => new ExitClearanceResource($clearance),
        ]);
    }

    /** DELETE /admin /exit-clearances/{clearance} */
    public function destroy(TerminationClearance $clearance): JsonResponse
    {
        $clearance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item clearance berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===================== EMPLOYEE (data sendiri) =====================

    /** GET /employee /exit-clearances — status clearance milik sendiri */
    public function myIndex(Request $request): JsonResponse
    {
        $emp = $request->user();
        $items = TerminationClearance::query()
            ->with('termination.employee:id,employee_id,name')
            ->whereHas('termination', fn ($q) => $q->where('employee_id', $emp->id))
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Exit clearance saya',
            'data' => ExitClearanceResource::collection($items)->response()->getData(true),
        ]);
    }

    private function ensureBelongsTo(TerminationClearance $item, int $terminationId): void
    {
        if ($item->termination_id !== $terminationId) {
            throw ValidationException::withMessages(['item' => 'Item bukan milik terminasi tersebut.']);
        }
    }
}

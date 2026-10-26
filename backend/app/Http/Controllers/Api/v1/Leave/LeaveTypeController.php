<?php

namespace App\Http\Controllers\Api\v1\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveTypeRequest;
use App\Http\Requests\Leave\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $types = LeaveType::query()
            ->when($request->boolean('only_active'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar tipe cuti',
            'data' => $types,
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $type = LeaveType::create($request->validated());
        return response()->json([
            'success' => true, 'message' => 'Tipe cuti berhasil dibuat', 'data' => $type,
        ], 201);
    }

    public function show(LeaveType $leaveType): JsonResponse
    {
        return response()->json([
            'success' => true, 'message' => 'Detail tipe cuti', 'data' => $leaveType,
        ]);
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): JsonResponse
    {
        $leaveType->update($request->validated());
        return response()->json([
            'success' => true, 'message' => 'Tipe cuti berhasil diubah', 'data' => $leaveType->fresh(),
        ]);
    }

    public function destroy(LeaveType $leaveType): JsonResponse
    {
        $leaveType->delete();
        return response()->json([
            'success' => true, 'message' => 'Tipe cuti berhasil dihapus', 'data' => null,
        ]);
    }
}

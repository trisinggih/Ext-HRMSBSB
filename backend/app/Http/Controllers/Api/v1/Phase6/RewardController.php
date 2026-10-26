<?php

namespace App\Http\Controllers\Api\v1\Phase6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reward\StoreRewardRequest;
use App\Http\Requests\Reward\UpdateRewardRequest;
use App\Http\Resources\Reward\RewardResource;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RewardController extends Controller
{
    /** GET /admin /rewards?employee_id= */
    public function index(Request $request): JsonResponse
    {
        $query = Reward::query()->with([
            'employee:id,employee_id,name',
            'payrollPeriod:id,name,period,start_date,end_date',
        ]);

        if ($request->has('employee_id') && $request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->has('payroll_period_id') && $request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', $request->integer('payroll_period_id'));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar reward',
            'data' => [
                'items' => RewardResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /** POST /admin /rewards */
    public function store(StoreRewardRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('rewards', 'public');
        } else {
            unset($data['attachment']);
        }

        $reward = Reward::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil dibuat',
            'data' => new RewardResource($reward),
        ], 201);
    }

    /** GET /admin /rewards/{reward} */
    public function show(Reward $reward): JsonResponse
    {
        $reward->load(['employee:id,employee_id,name', 'payrollPeriod:id,name,period,start_date,end_date']);

        return response()->json([
            'success' => true,
            'message' => 'Detail reward',
            'data' => new RewardResource($reward),
        ]);
    }

    /** PUT /admin /rewards/{reward} */
    public function update(UpdateRewardRequest $request, Reward $reward): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('attachment')) {
            $new = $request->file('attachment')->store('rewards', 'public');
            if ($reward->attachment) {
                Storage::disk('public')->delete($reward->attachment);
            }
            $data['attachment'] = $new;
        } else {
            unset($data['attachment']);
        }

        $reward->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil diperbarui',
            'data' => new RewardResource($reward->fresh()),
        ]);
    }

    /** DELETE /admin /rewards/{reward} */
    public function destroy(Reward $reward): JsonResponse
    {
        if ($reward->attachment) {
            Storage::disk('public')->delete($reward->attachment);
        }
        $reward->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===================== EMPLOYEE (data sendiri) =====================

    /** GET /employee /rewards */
    public function myIndex(Request $request): JsonResponse
    {
        $emp = $request->user();
        $items = Reward::where('employee_id', $emp->id)->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Reward saya',
            'data' => RewardResource::collection($items)->response()->getData(true),
        ]);
    }
}

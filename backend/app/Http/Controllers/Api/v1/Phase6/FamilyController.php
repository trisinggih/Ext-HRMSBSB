<?php

namespace App\Http\Controllers\Api\v1\Phase6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreFamilyRequest;
use App\Http\Requests\Family\UpdateFamilyRequest;
use App\Http\Resources\Family\FamilyResource;
use App\Models\EmployeeFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyController extends Controller
{
    /** GET /admin /families?employee_id= — list keluarga (admin) */
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeFamily::query()->with('employee:id,employee_id,name');

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar keluarga',
            'data' => [
                'items' => FamilyResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /** POST /admin /families */
    public function store(StoreFamilyRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['employee_id'])) {
            throw ValidationException::withMessages(['employee_id' => 'employee_id wajib diisi.']);
        }

        $family = EmployeeFamily::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Keluarga berhasil dibuat',
            'data' => new FamilyResource($family),
        ], 201);
    }

    /** GET /admin /families/{family} */
    public function show(EmployeeFamily $family): JsonResponse
    {
        $family->load('employee:id,employee_id,name');

        return response()->json([
            'success' => true,
            'message' => 'Detail keluarga',
            'data' => new FamilyResource($family),
        ]);
    }

    /** PUT /admin /families/{family} */
    public function update(UpdateFamilyRequest $request, EmployeeFamily $family): JsonResponse
    {
        $family->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Keluarga berhasil diperbarui',
            'data' => new FamilyResource($family->fresh()),
        ]);
    }

    /** DELETE /admin /families/{family} */
    public function destroy(EmployeeFamily $family): JsonResponse
    {
        $family->delete();

        return response()->json([
            'success' => true,
            'message' => 'Keluarga berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===================== EMPLOYEE (data sendiri) =====================

    /** GET /employee /family — keluarga milik sendiri */
    public function myIndex(Request $request): JsonResponse
    {
        $emp = $request->user();
        $items = EmployeeFamily::where('employee_id', $emp->id)->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Keluarga saya',
            'data' => FamilyResource::collection($items)->response()->getData(true),
        ]);
    }

    /** POST /employee /family — tambah anggota keluarga sendiri */
    public function myStore(StoreFamilyRequest $request): JsonResponse
    {
        $emp = $request->user();
        $family = EmployeeFamily::create(array_merge($request->validated(), ['employee_id' => $emp->id]));

        return response()->json([
            'success' => true,
            'message' => 'Anggota keluarga berhasil ditambahkan',
            'data' => new FamilyResource($family),
        ], 201);
    }

    /** PUT /employee /family/{family} */
    public function myUpdate(UpdateFamilyRequest $request, EmployeeFamily $family): JsonResponse
    {
        $emp = $request->user();
        $this->ensureOwner($family, $emp->id);
        $family->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Anggota keluarga berhasil diperbarui',
            'data' => new FamilyResource($family->fresh()),
        ]);
    }

    /** DELETE /employee /family/{family} */
    public function myDestroy(Request $request, EmployeeFamily $family): JsonResponse
    {
        $emp = $request->user();
        $this->ensureOwner($family, $emp->id);
        $family->delete();

        return response()->json([
            'success' => true,
            'message' => 'Anggota keluarga berhasil dihapus',
            'data' => null,
        ]);
    }

    private function ensureOwner(EmployeeFamily $family, int $employeeId): void
    {
        if ($family->employee_id !== $employeeId) {
            throw ValidationException::withMessages(['data' => 'Anda tidak memiliki akses ke data ini.']);
        }
    }
}

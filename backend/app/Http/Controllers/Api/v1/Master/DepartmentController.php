<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDepartmentRequest;
use App\Http\Requests\Master\UpdateDepartmentRequest;
use App\Http\Resources\Master\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $departments = Department::query()
            ->with(['manager', 'managerPosition', 'parent'])
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sort_by'), fn ($q) => $q->orderBy('departments.' . $request->sort_by, $request->get('sort_dir', 'asc')), fn ($q) => $q->orderBy('departments.name'))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar department',
            'data' => DepartmentResource::collection($departments)->response()->getData(true),
        ]);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $department = Department::create($data);
        
        $position = \App\Models\Position::create([
            'name' => 'Manager ' . $department->name,
            'code' => 'MGR-' . substr(strtoupper($department->code ?? substr($department->name, 0, 3)), 0, 10),
            'department_id' => $department->id,
            'branch_id' => null,
            'is_active' => true,
            'level' => 'Manager',
        ]);
        
        $department->update(['manager_position_id' => $position->id]);

        $this->syncManager($department);

        return response()->json([
            'success' => true,
            'message' => 'Department berhasil dibuat',
            'data' => new DepartmentResource($department->load(['branch', 'manager', 'managerPosition', 'parent'])),
        ], 201);
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail department',
            'data' => new DepartmentResource($department->load(['branch', 'manager', 'managerPosition', 'parent', 'divisions'])),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $data = $request->validated();
        
        if (isset($data['branch_id']) && !empty($data['code'])) {
            $branch = \App\Models\Branch::find($data['branch_id']);
            if ($branch && $branch->code) {
                if (!str_starts_with($data['code'], $branch->code . '-')) {
                    $data['code'] = $branch->code . '-' . $data['code'];
                }
            }
        }
        
        $department->update($data);
        
        if ($department->manager_position_id) {
            $position = \App\Models\Position::find($department->manager_position_id);
            if ($position) {
                $position->update([
                    'name' => 'Manager ' . $department->name,
                    'code' => 'MGR-' . substr(strtoupper($department->code ?? substr($department->name, 0, 3)), 0, 10),
                    'branch_id' => $department->branch_id,
                ]);
            }
        } else {
            $position = \App\Models\Position::create([
                'name' => 'Manager ' . $department->name,
                'code' => 'MGR-' . substr(strtoupper($department->code ?? substr($department->name, 0, 3)), 0, 10),
                'branch_id' => $department->branch_id,
                'is_active' => true,
                'level' => 'Manager',
            ]);
            $department->update(['manager_position_id' => $position->id]);
        }
        
        $this->syncManager($department);

        return response()->json([
            'success' => true,
            'message' => 'Department berhasil diperbarui',
            'data' => new DepartmentResource($department->load(['branch', 'manager', 'managerPosition', 'parent'])),
        ]);
    }
    
    private function syncManager(Department $department)
    {
        if ($department->manager_id) {
            $employee = \App\Models\Employee::find($department->manager_id);
            if ($employee) {
                $employee->department_id = $department->id;
                if ($department->manager_position_id) {
                    $employee->position_id = $department->manager_position_id;
                }
                $employee->save();
            }
        }
    }

    public function destroy(Department $department): JsonResponse
    {
        $positionId = $department->manager_position_id;
        
        $department->delete();
        
        if ($positionId) {
            \App\Models\Position::where('id', $positionId)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Department berhasil dihapus',
            'data' => null,
        ]);
    }
}


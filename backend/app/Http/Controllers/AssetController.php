<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = Asset::query()
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });

        // Summary counts
        $totalCount = (clone $baseQuery)->count();
        $availableCount = (clone $baseQuery)->where('status', 'Tersedia')->count();
        $distributedCount = (clone $baseQuery)->where('status', 'Dipinjamkan')->count();
        $maintenanceCount = (clone $baseQuery)->where('status', 'Pemeliharaan')->count();

        $query = (clone $baseQuery)
            ->with(['employee:id,name', 'company:id,name', 'branch:id,name,code', 'assetType:id,name,code'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('type', $request->type);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('inventory_number', 'like', "%{$s}%")
                        ->orWhere('serial_number', 'like', "%{$s}%")
                        ->orWhereHas('employee', function ($emp) use ($s) {
                            $emp->where('name', 'like', "%{$s}%");
                        });
                });
            })
            ->latest();

        if ($request->filled('per_page') && !$request->boolean('all')) {
            $paginated = $query->paginate($request->integer('per_page', 25));
            return response()->json([
                'status' => 'success',
                'data' => [
                    'data' => $paginated->items(),
                    'meta' => [
                        'current_page' => $paginated->currentPage(),
                        'last_page' => $paginated->lastPage(),
                        'per_page' => $paginated->perPage(),
                        'total' => $paginated->total(),
                        'from' => $paginated->firstItem(),
                        'to' => $paginated->lastItem(),
                    ]
                ],
                'summary' => [
                    'total' => $totalCount,
                    'available' => $availableCount,
                    'distributed' => $distributedCount,
                    'maintenance' => $maintenanceCount,
                    'distributed_percentage' => $totalCount > 0 ? round(($distributedCount / $totalCount) * 100, 1) : 0,
                ]
            ]);
        }

        $assets = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $assets,
            'summary' => [
                'total' => $totalCount,
                'available' => $availableCount,
                'distributed' => $distributedCount,
                'maintenance' => $maintenanceCount,
                'distributed_percentage' => $totalCount > 0 ? round(($distributedCount / $totalCount) * 100, 1) : 0,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string',
            'type' => 'nullable',
            'status' => 'nullable|in:Tersedia,Dipinjamkan,Pemeliharaan',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'inventory_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (empty($data['quantity'])) {
            $data['quantity'] = 1;
        }
        if (empty($data['unit'])) {
            $data['unit'] = 'Unit';
        }
        if (empty($data['type'])) {
            $data['type'] = 'Lainnya';
        }

        if (empty($data['inventory_number'])) {
            $data['inventory_number'] = $this->buildInventoryNumber(
                $data['company_id'] ?? null,
                $data['branch_id'] ?? null,
                $data['type'] ?? null
            );
        }

        $asset = Asset::create($data);

        return response()->json(['status' => 'success', 'message' => 'Asset created', 'data' => $asset->load('branch', 'company', 'employee')]);
    }

    public function generateNumber(Request $request)
    {
        $invNumber = $this->buildInventoryNumber(
            $request->company_id,
            $request->branch_id,
            $request->type
        );

        return response()->json(['status' => 'success', 'data' => $invNumber]);
    }

    private function buildInventoryNumber($companyId, $branchId = null, $typeId = null)
    {
        $branchCode = 'PM1';
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch && !empty($branch->code)) {
                $branchCode = strtoupper(trim($branch->code));
            }
        } elseif ($companyId) {
            $firstBranch = \App\Models\Branch::where('company_id', $companyId)->first();
            if ($firstBranch && !empty($firstBranch->code)) {
                $branchCode = strtoupper(trim($firstBranch->code));
            } else {
                $company = \App\Models\Company::find($companyId);
                $branchCode = $company ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $company->name), 0, 3)) : 'PM1';
            }
        }

        $typeCode = 'GEN';
        if ($typeId) {
            $typeObj = is_numeric($typeId) ? \App\Models\AssetType::find($typeId) : \App\Models\AssetType::where('code', $typeId)->orWhere('name', $typeId)->first();
            if ($typeObj) {
                $typeCode = strtoupper(trim($typeObj->code ?: substr($typeObj->name, 0, 3)));
            } elseif (is_string($typeId)) {
                $typeCode = strtoupper(substr(trim($typeId), 0, 3));
            }
        }

        $year = date('Y');
        $prefix = "{$branchCode}-AST-{$typeCode}-{$year}-";

        $latest = Asset::where('inventory_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $counter = 1;
        if ($latest && preg_match('/(\d{4})$/', $latest->inventory_number, $matches)) {
            $counter = intval($matches[1]) + 1;
        }

        do {
            $invNumber = $prefix . sprintf('%04d', $counter);
            $exists = Asset::where('inventory_number', $invNumber)->exists();
            if ($exists) {
                $counter++;
            }
        } while ($exists);

        return $invNumber;
    }

    public function show($id)
    {
        $asset = Asset::with('employee:id,name', 'company:id,name', 'branch:id,name,code', 'assetType:id,name,code')->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $asset]);
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string',
            'type' => 'nullable',
            'status' => 'nullable|in:Tersedia,Dipinjamkan,Pemeliharaan',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'inventory_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['quantity']) && empty($data['quantity'])) {
            $data['quantity'] = 1;
        }

        $asset->update($data);

        return response()->json(['status' => 'success', 'message' => 'Asset updated', 'data' => $asset->load('branch', 'company', 'employee')]);
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();

        return response()->json(['status' => 'success', 'message' => 'Asset deleted']);
    }

    public function myAssets(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $hasSubordinates = $user->hasSubordinates();
        $assets = Asset::where('employee_id', $user->id)->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $assets,
            'has_subordinates' => $hasSubordinates,
            'can_request_assets' => $hasSubordinates,
            'subordinates_count' => $user->subordinatesCount(),
        ]);
    }

    public function employeeAssets($employee_id)
    {
        $assets = Asset::where('employee_id', $employee_id)->latest()->get();
        return response()->json(['status' => 'success', 'data' => $assets]);
    }
}

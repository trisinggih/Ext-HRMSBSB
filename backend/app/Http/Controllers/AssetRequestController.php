<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetApprovalSetting;
use App\Models\AssetRequest;
use App\Models\AssetRequestApproval;
use App\Models\AssetRequestItem;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AssetRequestController extends Controller
{
    /**
     * List all asset requests (Web Admin)
     */
    public function index(Request $request)
    {
        $query = AssetRequest::with([
            'requester:id,name,employee_id',
            'forEmployee:id,name,employee_id',
            'department:id,name,manager_id',
            'manager:id,name,employee_id',
            'acknowledgedBy:id,name,employee_id',
            'approvedBy:id,name,employee_id',
            'assetType:id,name,code',
            'company:id,name',
            'branch:id,name,code',
            'asset:id,name,inventory_number,status',
            'items',
            'approvals.approver:id,name,employee_id',
        ])
        ->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        })
        ->when($request->filled('request_type'), function ($q) use ($request) {
            $q->where('request_type', $request->request_type);
        })
        ->when($request->filled('company_id'), function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        })
        ->when($request->filled('branch_id'), function ($q) use ($request) {
            $q->where('branch_id', $request->branch_id);
        })
        ->when($request->filled('department_id'), function ($q) use ($request) {
            $q->where('department_id', $request->department_id);
        })
        ->when($request->filled('search'), function ($q) use ($request) {
            $s = $request->search;
            $q->where(function ($sub) use ($s) {
                $sub->where('item_name', 'like', "%{$s}%")
                    ->orWhere('for_who', 'like', "%{$s}%")
                    ->orWhere('purpose', 'like', "%{$s}%")
                    ->orWhere('placement', 'like', "%{$s}%")
                    ->orWhere('item_size', 'like', "%{$s}%");
            });
        })
        ->latest();

        if ($request->has('per_page') || $request->has('page')) {
            $perPage = (int) $request->get('per_page', 10);
            $data = $query->paginate($perPage);
            return response()->json($data);
        }

        $data = $query->get();
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * List current user's asset requests (Mobile)
     */
    public function myRequests(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $hasSubordinates = $user->hasSubordinates();
        if (!$hasSubordinates) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'has_subordinates' => false,
                'can_request_assets' => false,
            ]);
        }

        $requests = AssetRequest::with([
            'requester:id,name,employee_id',
            'forEmployee:id,name,employee_id',
            'department:id,name',
            'assetType:id,name,code',
            'asset:id,name,inventory_number,status',
            'items',
            'approvals.approver:id,name',
        ])
        ->where('requester_id', $user->id)
        ->latest()
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests,
            'has_subordinates' => true,
            'can_request_assets' => true,
        ]);
    }

    /**
     * Store a new asset request (Mobile or Web)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_type' => 'nullable|in:pengadaan_baru,pergantian_alat,kebutuhan_karyawan_baru,kebutuhan_rutin,kebutuhan_perbaikan',
            'item_name' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'item_size' => 'nullable|string|max:255',
            'placement' => 'nullable|string|max:255',
            'purpose' => 'required|string',
            'for_who' => 'nullable|string|max:255',
            'for_employee_id' => 'nullable|exists:employees,id',
            'asset_type_id' => 'nullable|exists:asset_types,id',
            'quantity' => 'nullable|integer|min:1',
            'estimated_price' => 'nullable|numeric|min:0',
            'specifications' => 'nullable|string',
            'notes' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'submission_month' => 'nullable|string|max:50',
            'requester_id' => 'nullable|exists:employees,id',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_size' => 'nullable|string|max:255',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.placement' => 'nullable|string|max:255',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Tentukan pemohon
        if (empty($data['requester_id'])) {
            $user = $request->user();
            $data['requester_id'] = $user ? $user->id : null;
        }

        $emp = $data['requester_id'] ? Employee::with('primaryAssignment')->find($data['requester_id']) : null;

        // Validasi: hanya atasan yang memiliki bawahan yang dapat melakukan pengajuan permintaan aset
        if (!$emp || !$emp->hasSubordinates()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan permintaan aset hanya dapat dilakukan oleh atasan yang memiliki bawahan.'
            ], 403);
        }

        // Auto-assign company / branch / department jika kosong
        if ($emp && $emp->primaryAssignment) {
            if (empty($data['company_id'])) {
                $data['company_id'] = $emp->primaryAssignment->company_id;
            }
            if (empty($data['branch_id'])) {
                $data['branch_id'] = $emp->primaryAssignment->branch_id;
            }
            if (empty($data['department_id'])) {
                $data['department_id'] = $emp->primaryAssignment->department_id;
            }
        }

        // Tentukan Manager Departemen jika belum ada
        if (empty($data['manager_id']) && !empty($data['department_id'])) {
            $dept = Department::find($data['department_id']);
            if ($dept && $dept->manager_id) {
                $data['manager_id'] = $dept->manager_id;
            }
        }

        // Default bulan pengajuan jika kosong
        if (empty($data['submission_month'])) {
            $data['submission_month'] = now()->translatedFormat('F Y');
        }

        // Tipe pengajuan default
        $data['request_type'] = $data['request_type'] ?? 'kebutuhan_rutin';

        // Default untuk siapa jika kosong
        if (empty($data['for_who']) && !empty($data['for_employee_id'])) {
            $forEmp = Employee::find($data['for_employee_id']);
            $data['for_who'] = $forEmp ? $forEmp->name : 'Karyawan Baru';
        } elseif (empty($data['for_who'])) {
            if ($data['request_type'] === 'kebutuhan_karyawan_baru') {
                $data['for_who'] = 'Karyawan Baru';
            } elseif ($data['request_type'] === 'kebutuhan_rutin') {
                $deptName = !empty($data['department_id']) ? Department::find($data['department_id'])->name ?? 'Departemen' : 'Departemen';
                $data['for_who'] = $deptName;
            } else {
                $data['for_who'] = $emp ? $emp->name : 'Operasional';
            }
        }

        // Default penempatan jika kosong
        if (empty($data['placement'])) {
            $data['placement'] = $data['for_who'];
        }

        $data['status'] = 'pending';
        $data['quantity'] = $data['quantity'] ?? 1;

        // Jika items ada, kalkulasi total estimated_price dan quantity
        $itemsData = $data['items'] ?? [];
        unset($data['items']);

        if (!empty($itemsData)) {
            $totalEst = 0;
            $totalQty = 0;
            foreach ($itemsData as &$item) {
                $qty = $item['quantity'] ?? 1;
                $totalQty += $qty;
                $price = $item['estimated_price'] ?? 0;
                if ($price <= 0 && !empty($item['unit_price'])) {
                    $price = $item['unit_price'] * $qty;
                    $item['estimated_price'] = $price;
                }
                $totalEst += $price;
            }
            if ($totalEst > 0) {
                $data['estimated_price'] = $totalEst;
            }
            if ($totalQty > 0) {
                $data['quantity'] = $totalQty;
            }
            if (empty($data['item_name'])) {
                $firstItem = $itemsData[0]['item_name'] ?? 'Permintaan Aset';
                $itemCount = count($itemsData);
                $data['item_name'] = $itemCount > 1 ? ($firstItem . ' (+' . ($itemCount - 1) . ' item lainnya)') : $firstItem;
            }
        }

        if (empty($data['item_name'])) {
            return response()->json(['status' => 'error', 'message' => 'Nama barang aset wajib diisi.'], 422);
        }

        DB::beginTransaction();
        try {
            $assetRequest = AssetRequest::create($data);

            // Simpan items
            if (!empty($itemsData)) {
                foreach ($itemsData as $it) {
                    $assetRequest->items()->create([
                        'item_name' => $it['item_name'],
                        'item_size' => $it['item_size'] ?? null,
                        'quantity' => $it['quantity'] ?? 1,
                        'placement' => $it['placement'] ?? $assetRequest->placement,
                        'unit_price' => $it['unit_price'] ?? null,
                        'estimated_price' => $it['estimated_price'] ?? 0,
                        'notes' => $it['notes'] ?? null,
                    ]);
                }
            } else {
                // Buat 1 item default agar relasi items selalu tersedia
                $assetRequest->items()->create([
                    'item_name' => $assetRequest->item_name,
                    'item_size' => $assetRequest->item_size,
                    'quantity' => $assetRequest->quantity,
                    'placement' => $assetRequest->placement ?: $assetRequest->for_who,
                    'unit_price' => $assetRequest->quantity > 0 ? ($assetRequest->estimated_price / $assetRequest->quantity) : null,
                    'estimated_price' => $assetRequest->estimated_price ?: 0,
                    'notes' => $assetRequest->specifications,
                ]);
            }

            // Inisialisasi Alur Approval dari AssetApprovalSetting
            $this->initializeApprovalFlow($assetRequest);

            // Dispatch ke Approval Engine agar masuk ke antrean menu Approval (Mobile & Web)
            try {
                $payload = [
                    'item_name' => $assetRequest->item_name,
                    'quantity' => $assetRequest->quantity,
                    'unit_price' => $assetRequest->quantity > 0 ? ($assetRequest->estimated_price / $assetRequest->quantity) : 0,
                    'estimated_price' => $assetRequest->estimated_price,
                    'purpose' => $assetRequest->purpose,
                    'request_type' => $assetRequest->request_type,
                    'for_who' => $assetRequest->for_who,
                    'placement' => $assetRequest->placement,
                    'notes' => $assetRequest->notes ?: $assetRequest->specifications,
                    'items' => $assetRequest->items->map(fn($it) => [
                        'id' => $it->id,
                        'item_name' => $it->item_name,
                        'item_size' => $it->item_size,
                        'quantity' => $it->quantity,
                        'placement' => $it->placement,
                        'unit_price' => (float) $it->unit_price,
                        'estimated_price' => (float) $it->estimated_price,
                        'notes' => $it->notes,
                    ])->toArray(),
                ];

                app(\App\Services\ApprovalEngineService::class)->dispatch(
                    'asset_request',
                    $assetRequest->id,
                    $assetRequest->requester_id,
                    $payload
                );
            } catch (\Throwable $th) {
                \Illuminate\Support\Facades\Log::warning('ApprovalEngine dispatch failed for AssetRequest: ' . $th->getMessage());
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan pengadaan / perbaikan aset berhasil dikirim.',
                'data' => $assetRequest->load([
                    'requester',
                    'forEmployee',
                    'department',
                    'manager',
                    'assetType',
                    'branch',
                    'company',
                    'items',
                    'approvals.approver',
                ]),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show single asset request
     */
    public function show($id)
    {
        $assetRequest = AssetRequest::with([
            'requester',
            'forEmployee',
            'department',
            'manager',
            'acknowledgedBy',
            'approvedBy',
            'assetType',
            'company',
            'branch',
            'asset',
            'items',
            'approvals.approver',
        ])->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => $assetRequest]);
    }

    /**
     * Update asset request
     */
    public function update(Request $request, $id)
    {
        $assetRequest = AssetRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'request_type' => 'sometimes|in:pengadaan_baru,pergantian_alat,kebutuhan_karyawan_baru,kebutuhan_rutin,kebutuhan_perbaikan',
            'item_name' => 'sometimes|required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'item_size' => 'nullable|string|max:255',
            'placement' => 'nullable|string|max:255',
            'purpose' => 'sometimes|required|string',
            'for_who' => 'nullable|string|max:255',
            'for_employee_id' => 'nullable|exists:employees,id',
            'asset_type_id' => 'nullable|exists:asset_types,id',
            'quantity' => 'nullable|integer|min:1',
            'estimated_price' => 'nullable|numeric|min:0',
            'specifications' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,rejected,purchased',
            'admin_notes' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'submission_month' => 'nullable|string|max:50',
            'items' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $itemsData = $data['items'] ?? null;
        unset($data['items']);

        DB::beginTransaction();
        try {
            $assetRequest->update($data);

            if (is_array($itemsData)) {
                $assetRequest->items()->delete();
                $totalEst = 0;
                foreach ($itemsData as $it) {
                    $qty = $it['quantity'] ?? 1;
                    $price = $it['estimated_price'] ?? 0;
                    if ($price <= 0 && !empty($it['unit_price'])) {
                        $price = $it['unit_price'] * $qty;
                    }
                    $totalEst += $price;

                    $assetRequest->items()->create([
                        'item_name' => $it['item_name'],
                        'item_size' => $it['item_size'] ?? null,
                        'quantity' => $qty,
                        'placement' => $it['placement'] ?? $assetRequest->placement,
                        'unit_price' => $it['unit_price'] ?? null,
                        'estimated_price' => $price,
                        'notes' => $it['notes'] ?? null,
                    ]);
                }
                if ($totalEst > 0) {
                    $assetRequest->update(['estimated_price' => $totalEst]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan aset diperbarui',
                'data' => $assetRequest->fresh([
                    'requester',
                    'forEmployee',
                    'department',
                    'manager',
                    'acknowledgedBy',
                    'approvedBy',
                    'assetType',
                    'branch',
                    'company',
                    'asset',
                    'items',
                    'approvals.approver',
                ]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Multi-level approval handler (Mengetahui & Disetujui)
     */
    public function approveStep(Request $request, $id)
    {
        $assetRequest = AssetRequest::with(['approvals'])->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'level' => 'required|in:1,2', // 1: Mengetahui, 2: Disetujui
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string',
            'approver_id' => 'nullable|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $level = intval($request->level);
        $status = $request->status;
        $approverId = $request->approver_id ?: ($request->user() ? $request->user()->id : null);

        // Cari atau buat record approval
        $approval = AssetRequestApproval::where('asset_request_id', $assetRequest->id)
            ->where('level', $level)
            ->first();

        if (!$approval) {
            $approval = AssetRequestApproval::create([
                'asset_request_id' => $assetRequest->id,
                'level' => $level,
                'role_name' => $level === 1 ? 'Mengetahui' : 'Disetujui',
                'approver_id' => $approverId,
            ]);
        }

        $approval->update([
            'approver_id' => $approverId ?: $approval->approver_id,
            'status' => $status,
            'notes' => $request->notes,
            'action_date' => now(),
        ]);

        // Sinkronisasi status di asset_requests
        if ($level === 1) { // Mengetahui
            if ($status === 'approved') {
                $assetRequest->update([
                    'acknowledged_by' => $approverId,
                    'acknowledged_at' => now(),
                    'manager_id' => $assetRequest->manager_id ?: $approverId,
                ]);
            } else {
                $assetRequest->update([
                    'status' => 'rejected',
                    'admin_notes' => 'Ditolak pada tahap Mengetahui: ' . ($request->notes ?: '-'),
                ]);
            }
        } elseif ($level === 2) { // Disetujui
            if ($status === 'approved') {
                $assetRequest->update([
                    'status' => 'approved',
                    'approved_by' => $approverId,
                    'approved_at' => now(),
                    'admin_notes' => $request->notes ?: 'Telah disetujui untuk pengadaan.',
                ]);
            } else {
                $assetRequest->update([
                    'status' => 'rejected',
                    'admin_notes' => 'Ditolak pada tahap Disetujui: ' . ($request->notes ?: '-'),
                ]);
            }
        }

        // Sinkronisasi ke ApprovalRequest jika ada
        try {
            $approvalReq = \App\Models\ApprovalRequest::where('module', 'asset_request')
                ->where('module_id', $assetRequest->id)
                ->whereIn('status', ['pending', 'in_review'])
                ->first();

            if ($approvalReq && $approvalReq->current_step === $level) {
                $engine = app(\App\Services\ApprovalEngineService::class);
                if ($status === 'approved') {
                    $engine->approve($approvalReq, null, $approverId, null, $request->notes);
                } else {
                    $engine->reject($approvalReq, null, $approverId, null, $request->notes);
                }
            }
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::warning('ApprovalEngine sync from approveStep failed: ' . $th->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Persetujuan tahap ' . ($level === 1 ? 'Mengetahui' : 'Disetujui') . ' berhasil diperbarui.',
            'data' => $assetRequest->fresh(['approvals.approver', 'acknowledgedBy', 'approvedBy', 'manager']),
        ]);
    }

    /**
     * Update status langsung (Legacy compatibility)
     */
    public function updateStatus(Request $request, $id)
    {
        $assetRequest = AssetRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,pending',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $assetRequest->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status pengajuan diperbarui menjadi ' . $request->status,
            'data' => $assetRequest,
        ]);
    }

    /**
     * Handle item purchased: automatically mark as purchased and create Asset
     */
    public function purchase(Request $request, $id)
    {
        $assetRequest = AssetRequest::with('items')->findOrFail($id);

        if ($assetRequest->status === 'purchased' && $assetRequest->asset_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan ini sudah ditandai dibeli dan telah menjadi aset.',
            ], 422);
        }

        $companyId = $request->company_id ?: $assetRequest->company_id;
        if (!$companyId) {
            $firstCompany = Company::first();
            $companyId = $firstCompany ? $firstCompany->id : null;
        }

        $branchId = $request->branch_id ?: $assetRequest->branch_id;
        $typeId = $request->asset_type_id ?: ($assetRequest->asset_type_id ?: ($request->type ?: 'Lainnya'));

        // Generate inventory number otomatis
        $inventoryNumber = $request->inventory_number;
        if (empty($inventoryNumber)) {
            $inventoryNumber = $this->buildInventoryNumber($companyId, $branchId, $typeId);
        }

        // Tentukan pemegang aset
        $assignedEmployeeId = $request->employee_id ?: ($assetRequest->for_employee_id ?: $assetRequest->requester_id);

        // Buat record Aset baru
        $asset = Asset::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'inventory_number' => $inventoryNumber,
            'employee_id' => $assignedEmployeeId,
            'name' => $request->item_name ?: ($assetRequest->item_name ?: 'Aset Baru'),
            'quantity' => $request->quantity ?: ($assetRequest->quantity ?: 1),
            'unit' => $request->unit ?: 'Unit',
            'serial_number' => $request->serial_number ?: null,
            'type' => $typeId ?: 'Lainnya',
            'status' => $assignedEmployeeId ? 'Dipinjamkan' : 'Tersedia',
            'purchase_date' => $request->purchase_date ?: now()->toDateString(),
            'notes' => $request->notes ?: ('Pengadaan dari pengajuan: ' . $assetRequest->purpose . ($assetRequest->specifications ? ' | ' . $assetRequest->specifications : '')),
        ]);

        // Perbarui status pengajuan
        $assetRequest->update([
            'status' => 'purchased',
            'purchased_at' => now(),
            'asset_id' => $asset->id,
            'admin_notes' => $request->admin_notes ?: 'Barang sudah dibeli dan resmi didaftarkan sebagai aset.',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Barang berhasil ditandai sudah dibeli dan otomatis didaftarkan sebagai aset!',
            'data' => [
                'asset_request' => $assetRequest->fresh(['asset', 'requester', 'forEmployee', 'items']),
                'asset' => $asset->fresh(['branch', 'company', 'employee', 'assetType']),
            ],
        ]);
    }

    /**
     * Export PDF format sesuai template Peby Motor
     */
    public function exportPdf($id)
    {
        $assetRequest = AssetRequest::with([
            'requester.assignments.position',
            'requester.assignments.department',
            'requester.assignments.division',
            'requester.latestSignature',
            'forEmployee',
            'department.manager.latestSignature',
            'manager.latestSignature',
            'acknowledgedBy.latestSignature',
            'approvedBy.latestSignature',
            'company',
            'branch',
            'items',
            'approvals.approver.latestSignature',
        ])->findOrFail($id);

        // Jika tipe pengajuan adalah Pergantian Alat atau Pengadaan Baru (Formulir Permohonan Peralatan Kerja)
        if (in_array($assetRequest->request_type, ['pergantian_alat', 'pengadaan_baru'])) {
            $requester = $assetRequest->requester;
            $primaryAssignment = $requester && $requester->assignments ? $requester->assignments->where('is_primary', true)->first() : null;

            $requesterName = $requester ? $requester->name : '-';
            $nip = $requester ? ($requester->employee_id ?: ($requester->nip ?: '-')) : '-';
            $golongan = '-';
            $jabatan = $primaryAssignment && $primaryAssignment->position ? $primaryAssignment->position->name : ($assetRequest->department ? $assetRequest->department->name : '-');
            $departemen = $assetRequest->department ? $assetRequest->department->name : ($primaryAssignment && $primaryAssignment->department ? $primaryAssignment->department->name : '-');
            $divisi = $primaryAssignment && $primaryAssignment->division ? $primaryAssignment->division->name : '-';

            // Nama & Entitas Approver
            $hrManagerEmp = null;
            $accountingManagerEmp = null;
            $directorEmp = null;

            if ($assetRequest->approvals) {
                foreach ($assetRequest->approvals as $app) {
                    $role = strtolower($app->role_name ?? '');
                    if (str_contains($role, 'hr') || str_contains($role, 'ga')) {
                        $hrManagerEmp = $app->approver;
                    } elseif (str_contains($role, 'accounting') || str_contains($role, 'finance')) {
                        $accountingManagerEmp = $app->approver;
                    } elseif (str_contains($role, 'direktur') || str_contains($role, 'director') || $app->level == 2) {
                        $directorEmp = $app->approver;
                    }
                }
            }

            if (!$hrManagerEmp && $assetRequest->acknowledgedBy) {
                $hrManagerEmp = $assetRequest->acknowledgedBy;
            }
            if (!$directorEmp && $assetRequest->approvedBy) {
                $directorEmp = $assetRequest->approvedBy;
            }

            $hrManagerName = $hrManagerEmp?->name;
            $accountingManagerName = $accountingManagerEmp?->name;
            $directorName = $directorEmp?->name;

            // Foto Tanda Tangan
            $isApproved = in_array($assetRequest->status, ['approved', 'completed', 'in_progress']);
            $isAcknowledged = in_array($assetRequest->status, ['approved', 'completed', 'in_progress', 'acknowledged']);

            $requesterSignature = $requester?->signature_base64;
            $hrManagerSignature = ($isAcknowledged && $hrManagerEmp) ? $hrManagerEmp->signature_base64 : null;
            $accountingManagerSignature = ($isApproved && $accountingManagerEmp) ? $accountingManagerEmp->signature_base64 : null;
            $directorSignature = ($isApproved && $directorEmp) ? $directorEmp->signature_base64 : null;

            $cityName = $assetRequest->branch ? ($assetRequest->branch->city ?: $assetRequest->branch->name) : 'Jakarta';
            $dateFormatted = \Carbon\Carbon::parse($assetRequest->created_at)->translatedFormat('d F Y');

            $pdf = Pdf::loadView('pdf.equipment_request_form', [
                'assetRequest' => $assetRequest,
                'requesterName' => $requesterName,
                'nip' => $nip,
                'golongan' => $golongan,
                'jabatan' => $jabatan,
                'departemen' => $departemen,
                'divisi' => $divisi,
                'cityName' => $cityName,
                'dateFormatted' => $dateFormatted,
                'hrManagerName' => $hrManagerName,
                'accountingManagerName' => $accountingManagerName,
                'directorName' => $directorName,
                'requesterSignature' => $requesterSignature,
                'hrManagerSignature' => $hrManagerSignature,
                'accountingManagerSignature' => $accountingManagerSignature,
                'directorSignature' => $directorSignature,
            ])->setPaper('A4', 'portrait');

            $fileName = 'Form_Permohonan_Peralatan_Kerja_' . ($assetRequest->id) . '_' . date('Ymd') . '.pdf';
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        }

        // Default template: Pengajuan Kebutuhan Perlengkapan Kantor
        $managerEmp = $assetRequest->acknowledgedBy 
            ?? $assetRequest->manager 
            ?? ($assetRequest->department?->manager ?? null);
        $managerName = $managerEmp?->name;

        $approverEmp = $assetRequest->approvedBy;
        if (!$approverEmp) {
            $step2 = $assetRequest->approvals->where('level', 2)->first();
            if ($step2 && $step2->approver) {
                $approverEmp = $step2->approver;
            }
        }
        $approverName = $approverEmp?->name;

        // Foto Tanda Tangan
        $isApproved = in_array($assetRequest->status, ['approved', 'completed', 'in_progress']);
        $isAcknowledged = in_array($assetRequest->status, ['approved', 'completed', 'in_progress', 'acknowledged']);

        $requesterSignature = $assetRequest->requester?->signature_base64;
        $managerSignature = ($isAcknowledged && $managerEmp) ? $managerEmp->signature_base64 : null;
        $approverSignature = ($isApproved && $approverEmp) ? $approverEmp->signature_base64 : null;

        $pdf = Pdf::loadView('pdf.asset_request_form', [
            'assetRequest' => $assetRequest,
            'managerName' => $managerName,
            'approverName' => $approverName,
            'requesterSignature' => $requesterSignature,
            'managerSignature' => $managerSignature,
            'approverSignature' => $approverSignature,
        ])->setPaper('A4', 'portrait');

        $fileName = 'Pengajuan_Perlengkapan_' . ($assetRequest->id) . '_' . date('Ymd') . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Delete asset request
     */
    public function destroy($id)
    {
        $assetRequest = AssetRequest::findOrFail($id);
        $assetRequest->delete();

        return response()->json(['status' => 'success', 'message' => 'Pengajuan aset berhasil dihapus']);
    }

    /**
     * Helper to initialize approval lines from settings
     */
    private function initializeApprovalFlow(AssetRequest $request)
    {
        $category = ($request->request_type === 'pengadaan_baru' || $request->request_type === 'pergantian_alat') 
            ? 'permohonan_alat' 
            : 'pengajuan_perlengkapan';

        // Cari setting approval aset yang paling spesifik
        $setting = AssetApprovalSetting::where('is_active', true)
            ->where(function ($q) use ($category) {
                $q->where('approval_category', $category)
                  ->orWhereNull('approval_category');
            })
            ->where(function ($q) use ($request) {
                $q->whereNull('company_id')->orWhere('company_id', $request->company_id);
            })
            ->where(function ($q) use ($request) {
                $q->whereNull('branch_id')->orWhere('branch_id', $request->branch_id);
            })
            ->where(function ($q) use ($request) {
                $q->whereNull('department_id')->orWhere('department_id', $request->department_id);
            })
            ->where(function ($q) use ($request) {
                $q->whereNull('request_type')->orWhere('request_type', $request->request_type);
            })
            ->orderByRaw('department_id IS NOT NULL DESC, branch_id IS NOT NULL DESC, company_id IS NOT NULL DESC')
            ->first();

        // Jika aturan approval memiliki daftar stages bertingkat (multi-stage)
        if ($setting && $setting->stages()->exists()) {
            $stages = $setting->stages()->orderBy('level')->get();

            // Cari atasan langsung dari karyawan pemohon
            $requester = $request->requester ?: \App\Models\Employee::find($request->requester_id);
            $directSupervisorId = null;
            $directSupervisorRole = 'Atasan Langsung';
            if ($requester) {
                $asg = $requester->primaryAssignment ?? $requester->assignments->first();
                $directSupervisorId = $asg?->supervisor_id;
                if (!$directSupervisorId && $asg?->department_id) {
                    $dept = \App\Models\Department::find($asg->department_id);
                    $directSupervisorId = $dept?->manager_id;
                }
            }
            if (!$directSupervisorId && $request->manager_id) {
                $directSupervisorId = $request->manager_id;
            }
            if ($directSupervisorId) {
                $spv = \App\Models\Employee::find($directSupervisorId);
                $directSupervisorRole = $spv?->position?->name ?? 'Atasan Langsung';
            }

            foreach ($stages as $stage) {
                $approverId = $stage->employee_id;
                $roleName = $stage->role_title;

                // Jika stage level 1 atau employee_id null, gunakan atasan langsung
                if (!$approverId && $stage->level === 1) {
                    $approverId = $directSupervisorId;
                    if (!$roleName || $roleName === 'Atasan Langsung') {
                        $roleName = $directSupervisorRole;
                    }
                }

                AssetRequestApproval::create([
                    'asset_request_id' => $request->id,
                    'level' => $stage->level,
                    'role_name' => $roleName ?: ('Tahap ' . $stage->level),
                    'approver_id' => $approverId,
                    'status' => 'pending',
                ]);
            }

            if ($stages->first()) {
                $firstApprover = $stages->first()->employee_id ?: $directSupervisorId;
                if ($firstApprover) {
                    $request->acknowledged_by = $firstApprover;
                }
            }
            if ($stages->last() && $stages->last()->employee_id) {
                $request->approved_by = $stages->last()->employee_id;
            }
            $request->saveQuietly();
            return;
        }

        // 1. Level 1: Mengetahui (Legacy fallback)
        $mengetahuiApproverId = null;
        if ($setting && $setting->mengetahui_employee_id) {
            $mengetahuiApproverId = $setting->mengetahui_employee_id;
        } elseif ($request->manager_id) {
            $mengetahuiApproverId = $request->manager_id;
        } elseif ($request->department_id) {
            $dept = Department::find($request->department_id);
            if ($dept && $dept->manager_id) {
                $mengetahuiApproverId = $dept->manager_id;
            }
        }

        if ($mengetahuiApproverId) {
            $request->acknowledged_by = $mengetahuiApproverId;
            $request->saveQuietly();
        }

        AssetRequestApproval::create([
            'asset_request_id' => $request->id,
            'level' => 1,
            'role_name' => $setting?->mengetahui_title ?: 'Mengetahui',
            'approver_id' => $mengetahuiApproverId,
            'status' => 'pending',
        ]);

        // 2. Level 2: Disetujui (Legacy fallback)
        $disetujuiApproverId = null;
        if ($setting && $setting->disetujui_employee_id) {
            $disetujuiApproverId = $setting->disetujui_employee_id;
        } else {
            // Default cari pimpinan / direktur
            $director = Employee::whereHas('assignments.position', function ($q) {
                $q->where('name', 'like', '%Direktur%')
                  ->orWhere('name', 'like', '%Director%')
                  ->orWhere('name', 'like', '%General Manager%');
            })->orWhereHas('position', function ($q) {
                $q->where('name', 'like', '%Direktur%')
                  ->orWhere('name', 'like', '%Director%')
                  ->orWhere('name', 'like', '%General Manager%');
            })->first();
            $disetujuiApproverId = $director ? $director->id : null;
        }

        if ($disetujuiApproverId) {
            $request->approved_by = $disetujuiApproverId;
            $request->saveQuietly();
        }

        AssetRequestApproval::create([
            'asset_request_id' => $request->id,
            'level' => 2,
            'role_name' => $setting?->disetujui_title ?: 'Disetujui',
            'approver_id' => $disetujuiApproverId,
            'status' => 'pending',
        ]);
    }

    /**
     * Helper to generate inventory number
     */
    private function buildInventoryNumber($companyId, $branchId = null, $typeId = null)
    {
        $branchCode = 'PM1';
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch && !empty($branch->code)) {
                $branchCode = strtoupper(trim($branch->code));
            }
        } elseif ($companyId) {
            $firstBranch = Branch::where('company_id', $companyId)->first();
            if ($firstBranch && !empty($firstBranch->code)) {
                $branchCode = strtoupper(trim($firstBranch->code));
            } else {
                $company = Company::find($companyId);
                $branchCode = $company ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $company->name), 0, 3)) : 'PM1';
            }
        }

        $typeCode = 'GEN';
        if ($typeId) {
            $typeObj = is_numeric($typeId) ? AssetType::find($typeId) : AssetType::where('code', $typeId)->orWhere('name', $typeId)->first();
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
}

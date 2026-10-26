<?php

namespace App\Http\Controllers;

use App\Models\AssetApprovalSetting;
use App\Models\AssetApprovalSettingStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AssetApprovalSettingController extends Controller
{
    /**
     * Display a listing of asset approval settings.
     */
    public function index(Request $request)
    {
        $query = AssetApprovalSetting::with([
            'company:id,name',
            'branch:id,name,code',
            'department:id,name',
            'mengetahuiEmployee:id,name,employee_id',
            'mengetahui2Employee:id,name,employee_id',
            'disetujuiEmployee:id,name,employee_id',
            'stages.employee:id,name,employee_id',
        ])
        ->when($request->filled('approval_category'), function ($q) use ($request) {
            $q->where('approval_category', $request->approval_category);
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
                $sub->where('name', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        })
        ->latest();

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    /**
     * Store a newly created asset approval setting with multi-level stages.
     */
    public function store(Request $request)
    {
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255',
            'approval_category' => 'required|in:pengajuan_perlengkapan,permohonan_alat,perbaikan_alat',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'request_type' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'stages' => 'nullable|array|min:1',
            'stages.*.level' => 'nullable|integer',
            'stages.*.stage_name' => 'nullable|string|max:255',
            'stages.*.employee_id' => 'required_with:stages|exists:employees,id',
            'stages.*.role_title' => 'nullable|string|max:255',
            'stages.*.notes' => 'nullable|string',
            // Legacy fallbacks jika tidak kirim array stages
            'mengetahui_employee_id' => 'nullable|exists:employees,id',
            'mengetahui_title' => 'nullable|string|max:255',
            'disetujui_employee_id' => 'nullable|exists:employees,id',
            'disetujui_title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $stagesInput = $request->input('stages', []);

        // Sync legacy fields dari array stages jika ada
        if (!empty($stagesInput) && is_array($stagesInput)) {
            $firstStage = $stagesInput[0];
            $lastStage = end($stagesInput);
            $payload['mengetahui_employee_id'] = $firstStage['employee_id'] ?? null;
            $payload['mengetahui_title'] = $firstStage['role_title'] ?? null;
            $payload['mengetahui_type'] = 'specific_employee';

            $payload['disetujui_employee_id'] = $lastStage['employee_id'] ?? null;
            $payload['disetujui_title'] = $lastStage['role_title'] ?? null;
            $payload['disetujui_type'] = 'specific_employee';

            if (count($stagesInput) >= 3) {
                $midStage = $stagesInput[1];
                $payload['mengetahui_2_employee_id'] = $midStage['employee_id'] ?? null;
                $payload['mengetahui_2_title'] = $midStage['role_title'] ?? null;
                $payload['mengetahui_2_type'] = 'specific_employee';
            }
        }

        return DB::transaction(function () use ($payload, $stagesInput) {
            $setting = AssetApprovalSetting::create([
                'name' => $payload['name'],
                'approval_category' => $payload['approval_category'],
                'company_id' => $payload['company_id'] ?? null,
                'branch_id' => $payload['branch_id'] ?? null,
                'department_id' => $payload['department_id'] ?? null,
                'request_type' => $payload['request_type'] ?? null,
                'mengetahui_type' => 'specific_employee',
                'mengetahui_employee_id' => $payload['mengetahui_employee_id'] ?? null,
                'mengetahui_title' => $payload['mengetahui_title'] ?? null,
                'mengetahui_2_type' => 'specific_employee',
                'mengetahui_2_employee_id' => $payload['mengetahui_2_employee_id'] ?? null,
                'mengetahui_2_title' => $payload['mengetahui_2_title'] ?? null,
                'disetujui_type' => 'specific_employee',
                'disetujui_employee_id' => $payload['disetujui_employee_id'] ?? null,
                'disetujui_title' => $payload['disetujui_title'] ?? null,
                'is_active' => $payload['is_active'] ?? true,
                'notes' => $payload['notes'] ?? null,
            ]);

            // Buat stages
            if (!empty($stagesInput) && is_array($stagesInput)) {
                $level = 1;
                foreach ($stagesInput as $st) {
                    if (!empty($st['employee_id'])) {
                        AssetApprovalSettingStage::create([
                            'asset_approval_setting_id' => $setting->id,
                            'level' => $level,
                            'stage_name' => $st['stage_name'] ?? ('Tahap ' . $level),
                            'employee_id' => $st['employee_id'],
                            'role_title' => $st['role_title'] ?? null,
                            'notes' => $st['notes'] ?? null,
                        ]);
                        $level++;
                    }
                }
            } else {
                // Fallback jika hanya input 2/3 stage legacy
                $level = 1;
                if (!empty($payload['mengetahui_employee_id'])) {
                    AssetApprovalSettingStage::create([
                        'asset_approval_setting_id' => $setting->id,
                        'level' => $level++,
                        'stage_name' => 'Tahap 1: Mengetahui',
                        'employee_id' => $payload['mengetahui_employee_id'],
                        'role_title' => $payload['mengetahui_title'] ?? 'Manager',
                    ]);
                }
                if (!empty($payload['mengetahui_2_employee_id'])) {
                    AssetApprovalSettingStage::create([
                        'asset_approval_setting_id' => $setting->id,
                        'level' => $level++,
                        'stage_name' => 'Tahap 2: Mengetahui (Finance)',
                        'employee_id' => $payload['mengetahui_2_employee_id'],
                        'role_title' => $payload['mengetahui_2_title'] ?? 'Finance Manager',
                    ]);
                }
                if (!empty($payload['disetujui_employee_id'])) {
                    AssetApprovalSettingStage::create([
                        'asset_approval_setting_id' => $setting->id,
                        'level' => $level++,
                        'stage_name' => 'Tahap Akhir: Disetujui',
                        'employee_id' => $payload['disetujui_employee_id'],
                        'role_title' => $payload['disetujui_title'] ?? 'Direktur',
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Konfigurasi approval aset berhasil disimpan',
                'data' => $setting->load(['company', 'branch', 'department', 'stages.employee', 'mengetahuiEmployee', 'disetujuiEmployee']),
            ], 201);
        });
    }

    /**
     * Display the specified setting.
     */
    public function show($id)
    {
        $setting = AssetApprovalSetting::with([
            'company',
            'branch',
            'department',
            'stages.employee',
            'mengetahuiEmployee',
            'mengetahui2Employee',
            'disetujuiEmployee',
        ])->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => $setting]);
    }

    /**
     * Update the specified setting.
     */
    public function update(Request $request, $id)
    {
        $setting = AssetApprovalSetting::findOrFail($id);
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'name' => 'sometimes|required|string|max:255',
            'approval_category' => 'sometimes|required|in:pengajuan_perlengkapan,permohonan_alat,perbaikan_alat',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'request_type' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'stages' => 'nullable|array|min:1',
            'stages.*.level' => 'nullable|integer',
            'stages.*.stage_name' => 'nullable|string|max:255',
            'stages.*.employee_id' => 'required_with:stages|exists:employees,id',
            'stages.*.role_title' => 'nullable|string|max:255',
            'stages.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $stagesInput = $request->input('stages', null);

        if ($stagesInput !== null && is_array($stagesInput) && count($stagesInput) > 0) {
            $firstStage = $stagesInput[0];
            $lastStage = end($stagesInput);
            $payload['mengetahui_employee_id'] = $firstStage['employee_id'] ?? null;
            $payload['mengetahui_title'] = $firstStage['role_title'] ?? null;

            $payload['disetujui_employee_id'] = $lastStage['employee_id'] ?? null;
            $payload['disetujui_title'] = $lastStage['role_title'] ?? null;

            if (count($stagesInput) >= 3) {
                $midStage = $stagesInput[1];
                $payload['mengetahui_2_employee_id'] = $midStage['employee_id'] ?? null;
                $payload['mengetahui_2_title'] = $midStage['role_title'] ?? null;
            } else {
                $payload['mengetahui_2_employee_id'] = null;
                $payload['mengetahui_2_title'] = null;
            }
        }

        return DB::transaction(function () use ($setting, $payload, $stagesInput) {
            $setting->update([
                'name' => $payload['name'] ?? $setting->name,
                'approval_category' => $payload['approval_category'] ?? $setting->approval_category,
                'company_id' => array_key_exists('company_id', $payload) ? $payload['company_id'] : $setting->company_id,
                'branch_id' => array_key_exists('branch_id', $payload) ? $payload['branch_id'] : $setting->branch_id,
                'department_id' => array_key_exists('department_id', $payload) ? $payload['department_id'] : $setting->department_id,
                'request_type' => array_key_exists('request_type', $payload) ? $payload['request_type'] : $setting->request_type,
                'mengetahui_employee_id' => $payload['mengetahui_employee_id'] ?? $setting->mengetahui_employee_id,
                'mengetahui_title' => $payload['mengetahui_title'] ?? $setting->mengetahui_title,
                'mengetahui_2_employee_id' => array_key_exists('mengetahui_2_employee_id', $payload) ? $payload['mengetahui_2_employee_id'] : $setting->mengetahui_2_employee_id,
                'mengetahui_2_title' => array_key_exists('mengetahui_2_title', $payload) ? $payload['mengetahui_2_title'] : $setting->mengetahui_2_title,
                'disetujui_employee_id' => $payload['disetujui_employee_id'] ?? $setting->disetujui_employee_id,
                'disetujui_title' => $payload['disetujui_title'] ?? $setting->disetujui_title,
                'is_active' => array_key_exists('is_active', $payload) ? $payload['is_active'] : $setting->is_active,
                'notes' => array_key_exists('notes', $payload) ? $payload['notes'] : $setting->notes,
            ]);

            if ($stagesInput !== null && is_array($stagesInput)) {
                $setting->stages()->delete();
                $level = 1;
                foreach ($stagesInput as $st) {
                    if (!empty($st['employee_id'])) {
                        AssetApprovalSettingStage::create([
                            'asset_approval_setting_id' => $setting->id,
                            'level' => $level,
                            'stage_name' => $st['stage_name'] ?? ('Tahap ' . $level),
                            'employee_id' => $st['employee_id'],
                            'role_title' => $st['role_title'] ?? null,
                            'notes' => $st['notes'] ?? null,
                        ]);
                        $level++;
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Konfigurasi approval aset berhasil diperbarui',
                'data' => $setting->load(['company', 'branch', 'department', 'stages.employee', 'mengetahuiEmployee', 'disetujuiEmployee']),
            ]);
        });
    }

    /**
     * Get single approval setting for a category.
     */
    public function getByCategory($category)
    {
        $setting = AssetApprovalSetting::with([
            'company:id,name',
            'stages.employee.assignments.position',
        ])
        ->where('approval_category', $category)
        ->latest()
        ->first();

        return response()->json([
            'status' => 'success',
            'data' => $setting,
        ]);
    }

    /**
     * Save single approval setting for a category (ensures only 1 setting per category).
     */
    public function saveByCategory(Request $request, $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'stages' => 'required|array|min:1',
            'stages.*.employee_id' => 'nullable',
            'stages.*.stage_name' => 'nullable|string|max:255',
            'stages.*.role_title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $stagesInput = $request->input('stages', []);

        $defaultName = match ($category) {
            'pengajuan_perlengkapan' => 'Approval Pengajuan Perlengkapan Kantor',
            'permohonan_alat' => 'Approval Permohonan Peralatan Kerja',
            'perbaikan_alat' => 'Approval Permohonan Perbaikan Alat Kerja',
            default => 'Approval Aset',
        };

        $firstStage = !empty($stagesInput) ? $stagesInput[0] : null;
        $firstEmpId = (!empty($firstStage['employee_id']) && $firstStage['employee_id'] !== 'direct_supervisor') 
            ? (int)$firstStage['employee_id'] 
            : null;
        $firstTitle = $firstStage['role_title'] ?? ($firstEmpId ? null : 'Atasan Langsung');

        $lastStage = !empty($stagesInput) ? end($stagesInput) : null;
        $lastEmpId = (!empty($lastStage['employee_id']) && $lastStage['employee_id'] !== 'direct_supervisor') 
            ? (int)$lastStage['employee_id'] 
            : null;

        return DB::transaction(function () use ($category, $defaultName, $stagesInput, $firstEmpId, $firstTitle, $lastEmpId, $lastStage, $request) {
            $setting = AssetApprovalSetting::where('approval_category', $category)->first();

            if (!$setting) {
                $setting = AssetApprovalSetting::create([
                    'name' => $request->input('name', $defaultName),
                    'approval_category' => $category,
                    'is_active' => true,
                    'mengetahui_type' => $firstEmpId ? 'specific_employee' : 'direct_supervisor',
                    'mengetahui_employee_id' => $firstEmpId,
                    'mengetahui_title' => $firstTitle,
                    'disetujui_type' => 'specific_employee',
                    'disetujui_employee_id' => $lastEmpId,
                    'disetujui_title' => $lastStage['role_title'] ?? null,
                    'notes' => $request->input('notes'),
                ]);
            } else {
                $setting->update([
                    'name' => $request->input('name', $setting->name ?: $defaultName),
                    'is_active' => true,
                    'mengetahui_type' => $firstEmpId ? 'specific_employee' : 'direct_supervisor',
                    'mengetahui_employee_id' => $firstEmpId,
                    'mengetahui_title' => $firstTitle,
                    'disetujui_type' => 'specific_employee',
                    'disetujui_employee_id' => $lastEmpId,
                    'disetujui_title' => $lastStage['role_title'] ?? null,
                    'notes' => $request->input('notes'),
                ]);
            }

            // Hapus duplikat setting lain untuk kategori ini agar selalu hanya 1 yang ada
            AssetApprovalSetting::where('approval_category', $category)
                ->where('id', '!=', $setting->id)
                ->delete();

            // Sync stages
            $setting->stages()->delete();
            $level = 1;
            foreach ($stagesInput as $st) {
                $empId = (!empty($st['employee_id']) && $st['employee_id'] !== 'direct_supervisor') 
                    ? (int)$st['employee_id'] 
                    : null;

                AssetApprovalSettingStage::create([
                    'asset_approval_setting_id' => $setting->id,
                    'level' => $level,
                    'stage_name' => 'Tahap ' . $level,
                    'employee_id' => $empId,
                    'role_title' => $st['role_title'] ?: ($level === 1 && !$empId ? 'Atasan Langsung' : null),
                    'notes' => $st['notes'] ?? null,
                ]);
                $level++;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pengaturan approval berhasil disimpan',
                'data' => $setting->load(['stages.employee.assignments.position']),
            ]);
        });
    }

    /**
     * Remove the specified setting.
     */
    public function destroy($id)
    {
        $setting = AssetApprovalSetting::findOrFail($id);
        $setting->delete();

        return response()->json(['status' => 'success', 'message' => 'Konfigurasi approval aset berhasil dihapus']);
    }
}

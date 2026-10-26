<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRepair;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetRepairController extends Controller
{
    /**
     * List all asset repairs
     */
    public function index(Request $request)
    {
        $query = AssetRepair::with([
            'employee:id,name,employee_id',
            'employee.position',
            'company:id,name',
            'branch:id,name,code',
            'department:id,name',
            'asset:id,name,inventory_number,status,serial_number',
            'acknowledgedBy:id,name,employee_id',
            'approvedBy:id,name,employee_id',
        ])
        ->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
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
        ->when($request->filled('employee_id'), function ($q) use ($request) {
            $q->where('employee_id', $request->employee_id);
        })
        ->when($request->filled('priority'), function ($q) use ($request) {
            $q->where('priority', $request->priority);
        })
        ->when($request->filled('search'), function ($q) use ($request) {
            $s = $request->search;
            $q->where(function ($sub) use ($s) {
                $sub->where('item_name', 'like', "%{$s}%")
                    ->orWhere('brand_model', 'like', "%{$s}%")
                    ->orWhere('asset_code', 'like', "%{$s}%")
                    ->orWhere('pic_name', 'like', "%{$s}%")
                    ->orWhere('damage_description', 'like', "%{$s}%")
                    ->orWhereHas('employee', function ($eq) use ($s) {
                        $eq->where('name', 'like', "%{$s}%")
                           ->orWhere('employee_id', 'like', "%{$s}%");
                    });
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
     * Store new asset repair request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'asset_id' => 'nullable|exists:assets,id',
            'brand_model' => 'nullable|string|max:255',
            'asset_code' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'condition' => 'nullable|in:rusak,tidak_berfungsi,lainnya',
            'condition_other' => 'nullable|string|max:255',
            'damage_description' => 'required|string',
            'repair_description' => 'nullable|string',
            'pic_name' => 'nullable|string|max:255',
            'operational_disrupted' => 'nullable|boolean',
            'estimated_cost' => 'nullable|numeric|min:0',
            'priority' => 'nullable|in:rendah,sedang,tinggi',
            'bank_account_info' => 'nullable|string|max:255',
            'submission_date' => 'nullable|date',
            'repair_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (empty($data['submission_date'])) {
            $data['submission_date'] = now()->toDateString();
        }
        $data['submission_month'] = Carbon::parse($data['submission_date'])->translatedFormat('F Y');
        $data['quantity'] = $data['quantity'] ?? 1;
        $data['condition'] = $data['condition'] ?? 'rusak';
        $data['priority'] = $data['priority'] ?? 'sedang';
        $data['operational_disrupted'] = $request->boolean('operational_disrupted');
        $data['status'] = 'pending';

        // Auto-fill pic_name if employee selected
        if (!empty($data['employee_id']) && empty($data['pic_name'])) {
            $emp = Employee::find($data['employee_id']);
            if ($emp) {
                $data['pic_name'] = $emp->name;
            }
        }

        // Auto-assign approval lines dari AssetApprovalSetting (kategori perbaikan_alat)
        $setting = \App\Models\AssetApprovalSetting::where('is_active', true)
            ->where(function ($q) {
                $q->where('approval_category', 'perbaikan_alat')
                  ->orWhereNull('approval_category');
            })
            ->where(function ($q) use ($data) {
                if (!empty($data['company_id'])) {
                    $q->whereNull('company_id')->orWhere('company_id', $data['company_id']);
                }
            })
            ->where(function ($q) use ($data) {
                if (!empty($data['branch_id'])) {
                    $q->whereNull('branch_id')->orWhere('branch_id', $data['branch_id']);
                }
            })
            ->where(function ($q) use ($data) {
                if (!empty($data['department_id'])) {
                    $q->whereNull('department_id')->orWhere('department_id', $data['department_id']);
                }
            })
            ->orderByRaw('department_id IS NOT NULL DESC, branch_id IS NOT NULL DESC, company_id IS NOT NULL DESC')
            ->first();

        if ($setting) {
            $repairRequester = \App\Models\Employee::find($data['employee_id']);
            $directSupervisorId = null;
            if ($repairRequester) {
                $asg = $repairRequester->primaryAssignment ?? $repairRequester->assignments->first();
                $directSupervisorId = $asg?->supervisor_id;
                if (!$directSupervisorId && $asg?->department_id) {
                    $dept = \App\Models\Department::find($asg->department_id);
                    $directSupervisorId = $dept?->manager_id;
                }
            }

            if ($setting->stages()->exists()) {
                $firstStage = $setting->stages()->orderBy('level')->first();
                $lastStage = $setting->stages()->orderBy('level', 'desc')->first();

                if (empty($data['acknowledged_by'])) {
                    $data['acknowledged_by'] = $firstStage?->employee_id ?: $directSupervisorId;
                }
                if (empty($data['approved_by'])) {
                    $data['approved_by'] = $lastStage?->employee_id;
                }
            } else {
                if ($setting->mengetahui_employee_id && empty($data['acknowledged_by'])) {
                    $data['acknowledged_by'] = $setting->mengetahui_employee_id;
                } elseif (empty($data['acknowledged_by']) && $directSupervisorId) {
                    $data['acknowledged_by'] = $directSupervisorId;
                }
                if ($setting->disetujui_employee_id && empty($data['approved_by'])) {
                    $data['approved_by'] = $setting->disetujui_employee_id;
                }
            }
        }

        $repair = AssetRepair::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan perbaikan alat kerja berhasil diajukan',
            'data' => $repair->load(['employee', 'company', 'branch', 'department', 'asset']),
        ], 201);
    }

    /**
     * Show repair request detail
     */
    public function show($id)
    {
        $repair = AssetRepair::with([
            'employee.position',
            'employee.division',
            'employee.department',
            'employee.branch',
            'employee.company',
            'company',
            'branch',
            'department',
            'asset',
            'acknowledgedBy',
            'approvedBy',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $repair,
        ]);
    }

    /**
     * Update repair request
     */
    public function update(Request $request, $id)
    {
        $repair = AssetRepair::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_name' => 'sometimes|required|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'asset_id' => 'nullable|exists:assets,id',
            'brand_model' => 'nullable|string|max:255',
            'asset_code' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'condition' => 'nullable|in:rusak,tidak_berfungsi,lainnya',
            'condition_other' => 'nullable|string|max:255',
            'damage_description' => 'sometimes|required|string',
            'repair_description' => 'nullable|string',
            'pic_name' => 'nullable|string|max:255',
            'operational_disrupted' => 'nullable|boolean',
            'estimated_cost' => 'nullable|numeric|min:0',
            'priority' => 'nullable|in:rendah,sedang,tinggi',
            'bank_account_info' => 'nullable|string|max:255',
            'submission_date' => 'nullable|date',
            'repair_date' => 'nullable|date',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['submission_date'])) {
            $data['submission_month'] = Carbon::parse($data['submission_date'])->translatedFormat('F Y');
        }
        if ($request->has('operational_disrupted')) {
            $data['operational_disrupted'] = $request->boolean('operational_disrupted');
        }

        $repair->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan perbaikan alat kerja berhasil diperbarui',
            'data' => $repair->load(['employee', 'company', 'branch', 'department', 'asset']),
        ]);
    }

    /**
     * Update status & approval
     */
    public function updateStatus(Request $request, $id)
    {
        $repair = AssetRepair::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,in_repair,completed,rejected',
            'admin_notes' => 'nullable|string',
            'acknowledged_by' => 'nullable|exists:employees,id',
            'approved_by' => 'nullable|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = $request->status;
        $repair->status = $status;
        if ($request->filled('admin_notes')) {
            $repair->admin_notes = $request->admin_notes;
        }

        if ($request->filled('acknowledged_by')) {
            $repair->acknowledged_by = $request->acknowledged_by;
            $repair->acknowledged_at = now();
        }

        if ($request->filled('approved_by')) {
            $repair->approved_by = $request->approved_by;
            $repair->approved_at = now();
        }

        if ($status === 'approved' && !$repair->approved_at) {
            $repair->approved_at = now();
        }

        $repair->save();

        return response()->json([
            'status' => 'success',
            'message' => "Status permohonan berhasil diubah menjadi {$status}",
            'data' => $repair,
        ]);
    }

    /**
     * Delete repair request
     */
    public function destroy($id)
    {
        $repair = AssetRepair::findOrFail($id);
        $repair->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan perbaikan alat kerja berhasil dihapus',
        ]);
    }

    /**
     * Export Form Permohonan Perbaikan Alat Kerja PDF
     */
    public function exportPdf($id)
    {
        Carbon::setLocale('id');

        $repair = AssetRepair::with([
            'employee.position',
            'employee.division',
            'employee.department',
            'employee.branch',
            'employee.company',
            'employee.latestSignature',
            'company',
            'branch',
            'department',
            'asset',
            'acknowledgedBy.latestSignature',
            'approvedBy.latestSignature',
        ])->findOrFail($id);

        $emp = $repair->employee;

        // Requester details
        $requesterName = $emp ? $emp->name : ($repair->pic_name ?: '..........................................');
        $nip = $emp ? ($emp->employee_id ?: '-') : '..........................................';
        
        // Jabatan
        $jabatan = '-';
        if ($emp) {
            if ($emp->position) {
                $jabatan = $emp->position->name;
            } elseif ($emp->primaryAssignment && $emp->primaryAssignment->position) {
                $jabatan = $emp->primaryAssignment->position->name;
            }
        }
        if ($jabatan === '-' && $repair->pic_name) {
            $jabatan = 'Manager Operasional';
        }

        // Departemen & Divisi
        $departemen = '-';
        if ($repair->department) {
            $departemen = $repair->department->name;
        } elseif ($emp && $emp->department) {
            $departemen = $emp->department->name;
        }

        $divisi = '-';
        if ($emp && $emp->division) {
            $divisi = $emp->division->name;
        }

        // Cabang
        $cabang = '-';
        if ($repair->branch) {
            $cabang = $repair->branch->name;
        } elseif ($emp && $emp->branch) {
            $cabang = $emp->branch->name;
        }

        // Tanggal / Bulan Pengajuan
        $subDate = $repair->submission_date ? Carbon::parse($repair->submission_date) : Carbon::now();
        $submissionInfo = $subDate->translatedFormat('d F Y');
        if ($repair->submission_month && $repair->submission_month !== $submissionInfo) {
            $submissionInfo .= ' (' . $repair->submission_month . ')';
        }

        // Lokasi kota untuk titimangsa
        $cityName = 'Jakarta';
        if ($repair->branch && !empty($repair->branch->city)) {
            $cityName = $repair->branch->city;
        } elseif ($repair->branch && !empty($repair->branch->name)) {
            $cityName = $repair->branch->name;
        } elseif ($repair->company && !empty($repair->company->city)) {
            $cityName = $repair->company->city;
        }

        $dateFormatted = $subDate->translatedFormat('d F Y');

        // Cari konfigurasi approval khusus perbaikan alat yang aktif & cocok
        $setting = \App\Models\AssetApprovalSetting::with([
            'mengetahuiEmployee.latestSignature', 
            'mengetahui2Employee.latestSignature', 
            'disetujuiEmployee.latestSignature'
        ])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('approval_category', 'perbaikan_alat')
                  ->orWhereNull('approval_category');
            })
            ->where(function ($q) use ($repair) {
                $q->whereNull('company_id')->orWhere('company_id', $repair->company_id);
            })
            ->where(function ($q) use ($repair) {
                $q->whereNull('branch_id')->orWhere('branch_id', $repair->branch_id);
            })
            ->where(function ($q) use ($repair) {
                $q->whereNull('department_id')->orWhere('department_id', $repair->department_id);
            })
            ->orderByRaw('department_id IS NOT NULL DESC, branch_id IS NOT NULL DESC, company_id IS NOT NULL DESC')
            ->first();

        // Signatures (prioritas: transaksi > setting approval > default nama form fisik)
        $hrEmp = $repair->acknowledgedBy ?: ($setting?->mengetahuiEmployee);
        $financeEmp = $setting?->mengetahui2Employee;
        $directorEmp = $repair->approvedBy ?: ($setting?->disetujuiEmployee);

        $hrManagerName = $hrEmp ? $hrEmp->name : 'Agus Prasetyo';
        $hrManagerTitle = $setting?->mengetahui_title ?: 'Manager HR & GA';

        $financeManagerName = $financeEmp ? $financeEmp->name : 'Iswahyuni';
        $financeManagerTitle = $setting?->mengetahui_2_title ?: 'Manager Finance & Accounting';

        $directorName = $directorEmp ? $directorEmp->name : 'Ujang Peby Hidayat';
        $directorTitle = $setting?->disetujui_title ?: 'Direktur';

        // Foto Tanda Tangan
        $isApproved = in_array($repair->status, ['approved', 'completed', 'in_progress']);
        $isAcknowledged = in_array($repair->status, ['approved', 'completed', 'in_progress', 'acknowledged']);

        $requesterSignature = $emp?->signature_base64;
        $hrManagerSignature = ($isAcknowledged && $hrEmp) ? $hrEmp->signature_base64 : null;
        $financeManagerSignature = ($isApproved && $financeEmp) ? $financeEmp->signature_base64 : null;
        $directorSignature = ($isApproved && $directorEmp) ? $directorEmp->signature_base64 : null;

        $pdf = Pdf::loadView('pdf.asset_repair_form', [
            'repair' => $repair,
            'requesterName' => $requesterName,
            'nip' => $nip,
            'jabatan' => $jabatan,
            'departemen' => $departemen,
            'divisi' => $divisi,
            'cabang' => $cabang,
            'submissionInfo' => $submissionInfo,
            'cityName' => $cityName,
            'dateFormatted' => $dateFormatted,
            'hrManagerName' => $hrManagerName,
            'hrManagerTitle' => $hrManagerTitle,
            'financeManagerName' => $financeManagerName,
            'financeManagerTitle' => $financeManagerTitle,
            'directorName' => $directorName,
            'directorTitle' => $directorTitle,
            'requesterSignature' => $requesterSignature,
            'hrManagerSignature' => $hrManagerSignature,
            'financeManagerSignature' => $financeManagerSignature,
            'directorSignature' => $directorSignature,
        ])->setPaper('A4', 'portrait');

        $fileName = 'Form_Perbaikan_Alat_Kerja_' . ($repair->id) . '_' . date('Ymd') . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}

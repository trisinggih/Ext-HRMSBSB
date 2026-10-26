<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCompanyRequest;
use App\Http\Requests\Master\UpdateCompanyRequest;
use App\Http\Resources\Master\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = Company::query()
            ->with(['group', 'parentCompany'])
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('legal_name', 'like', "%{$request->search}%")
                  ->orWhere('npwp', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) =>
                $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sort_by'), 
                fn ($q) => $q->orderBy($request->sort_by, $request->get('sort_dir', 'asc')),
                fn ($q) => $q->orderBy('name')
            )
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar company',
            'data' => CompanyResource::collection($companies)->response()->getData(true),
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Company berhasil dibuat',
            'data' => new CompanyResource($company->load(['group', 'parentCompany'])),
        ], 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail company',
            'data' => new CompanyResource($company->load(['branches', 'group', 'parentCompany', 'childCompanies'])),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $data = $request->validated();
        
        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Company berhasil diperbarui',
            'data' => new CompanyResource($company->load(['group', 'parentCompany'])),
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company berhasil dihapus',
            'data' => null,
        ]);
    }

    public function approvalLines(Company $company, Request $request): JsonResponse
    {
        $module = $request->query('module', 'leave');
        $lines = \App\Models\CompanyApprovalLine::where('company_id', $company->id)
            ->where('module', $module)
            ->orderBy('level')
            ->with('specificUser:id,name,employee_id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar approval line',
            'data' => $lines,
        ]);
    }

    public function updateApprovalLines(Company $company, Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'required|string',
            'lines' => 'array',
            'lines.*.level' => 'required|integer|min:1',
            'lines.*.approver_type' => 'required|in:supervisor,manager,specific_user',
            'lines.*.specific_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $module = $request->module;
        
        \App\Models\CompanyApprovalLine::where('company_id', $company->id)
            ->where('module', $module)
            ->forceDelete();
            
        $newLines = [];
        if ($request->has('lines')) {
            foreach ($request->lines as $line) {
                $newLines[] = [
                    'company_id' => $company->id,
                    'module' => $module,
                    'level' => $line['level'],
                    'approver_type' => $line['approver_type'],
                    'specific_user_id' => $line['specific_user_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            \App\Models\CompanyApprovalLine::insert($newLines);
        }

        return response()->json([
            'success' => true,
            'message' => 'Approval lines berhasil diperbarui',
            'data' => \App\Models\CompanyApprovalLine::where('company_id', $company->id)
                ->where('module', $module)->orderBy('level')->get()
        ]);
    }
}

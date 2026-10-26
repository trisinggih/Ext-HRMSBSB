<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMutation;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequest;
use App\Services\ApprovalEngineService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class EmployeeMutationController extends Controller
{
    protected $approvalEngine;

    public function __construct(ApprovalEngineService $approvalEngine)
    {
        $this->approvalEngine = $approvalEngine;
    }

    public function index(Request $request)
    {
        $query = EmployeeMutation::with([
            'employee', 
            'oldCompany', 'oldBranch', 'oldDepartment', 'oldDivision', 'oldPosition',
            'newCompany', 'newBranch', 'newDepartment', 'newDivision', 'newPosition'
        ])
        ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
        ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
        ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
        ->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($sq) use ($search) {
                $sq->where('memo_number', 'like', "%{$search}%")
                   ->orWhereHas('employee', function ($eq) use ($search) {
                       $eq->where('name', 'like', "%{$search}%")
                          ->orWhere('employee_id', 'like', "%{$search}%");
                   });
            });
        })
        ->orderBy('created_at', 'desc');

        $mutations = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $mutations
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:mutasi,promosi,demosi',
            'effective_date' => 'required|date',
            'new_company_id' => 'required|exists:companies,id',
            'new_department_id' => 'required|exists:departments,id',
            'new_position_id' => 'required|exists:positions,id',
            'new_nip' => 'nullable|string|max:50',
            'prorate_payroll' => 'nullable|boolean',
            'approvers' => 'required|array|min:1',
        ]);

        try {
            DB::beginTransaction();

            $employee = Employee::with(['assignments', 'primaryAssignment'])->findOrFail($request->employee_id);
            $primaryAssignment = $employee->primaryAssignment ?? $employee->assignments->where('is_primary', true)->first() ?? $employee->assignments->first();

            $mutation = new EmployeeMutation();
            $mutation->memo_number = 'MEMO/' . date('Y/m') . '/' . rand(1000, 9999);
            $mutation->employee_id = $employee->id;
            $mutation->type = $request->type;
            
            // Old Assignment Data
            if ($primaryAssignment) {
                $mutation->old_company_id = $primaryAssignment->company_id;
                $mutation->old_branch_id = $primaryAssignment->branch_id;
                $mutation->old_department_id = $primaryAssignment->department_id;
                $mutation->old_division_id = $primaryAssignment->division_id;
                $mutation->old_position_id = $primaryAssignment->position_id;
                $mutation->old_supervisor_id = $primaryAssignment->supervisor_id;
            }

            // New Assignment Data
            $mutation->new_company_id = $request->new_company_id;
            $mutation->new_branch_id = $request->new_branch_id ?? ($primaryAssignment->branch_id ?? null);
            $mutation->new_department_id = $request->new_department_id;
            $mutation->new_division_id = $request->new_division_id ?? ($primaryAssignment->division_id ?? null);
            $mutation->new_position_id = $request->new_position_id;
            $mutation->new_supervisor_id = $request->new_supervisor_id ?? ($primaryAssignment->supervisor_id ?? null);
            
            $mutation->new_nip = $request->filled('new_nip') ? trim($request->new_nip) : null;
            $mutation->prorate_payroll = $request->boolean('prorate_payroll', true);
            $mutation->effective_date = $request->effective_date;
            $mutation->reason = $request->reason;
            $mutation->created_by = auth()->id();
            $mutation->status = 'pending';
            $mutation->save();

            // Create Custom Workflow for this specific mutation
            $moduleName = 'employee_mutations';
            $workflowName = "Mutasi {$mutation->id} Workflow";
            $workflow = ApprovalWorkflow::create([
                'name' => $workflowName,
                'module' => $moduleName,
                'description' => "Custom workflow for mutation {$mutation->id}",
                'is_active' => true,
            ]);

            // Add Approvers
            $approvers = $request->approvers; // array of user/employee IDs
            foreach ($approvers as $index => $approverId) {
                $workflow->steps()->create([
                    'approver_type' => 'specific_user',
                    'approver_id' => $approverId, // Assuming approver_id is user_id
                    'order' => $index + 1,
                    'is_optional' => ($index > 0), // 1st is mandatory, rest are optional
                ]);
            }

            // Fetch labels for rich approval payload in mobile & web
            $oldPosition = $mutation->oldPosition?->name ?? $primaryAssignment?->position?->name;
            $newPosition = \App\Models\Position::find($mutation->new_position_id)?->name;
            $oldBranch = $mutation->oldBranch?->name ?? $primaryAssignment?->branch?->name;
            $newBranch = \App\Models\Branch::find($mutation->new_branch_id)?->name;
            $oldCompany = $mutation->oldCompany?->name ?? $primaryAssignment?->company?->name;
            $newCompany = \App\Models\Company::find($mutation->new_company_id)?->name;
            $oldDepartment = $mutation->oldDepartment?->name ?? $primaryAssignment?->department?->name;
            $newDepartment = \App\Models\Department::find($mutation->new_department_id)?->name;

            // Dispatch Approval Request with employee ID as requester
            $approvalRequest = $this->approvalEngine->dispatch($moduleName, $mutation->id, $employee->id, [
                'type' => $mutation->type,
                'mutation_type' => $mutation->type,
                'memo_number' => $mutation->memo_number,
                'effective_date' => $mutation->effective_date,
                'reason' => $mutation->reason,
                'employee_name' => $employee->name,
                'employee_nik' => $employee->employee_id,
                'old_position_name' => $oldPosition,
                'new_position_name' => $newPosition,
                'old_branch_name' => $oldBranch,
                'new_branch_name' => $newBranch,
                'old_company_name' => $oldCompany,
                'new_company_name' => $newCompany,
                'old_department_name' => $oldDepartment,
                'new_department_name' => $newDepartment,
                'custom_workflow_id' => $workflow->id
            ]);
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Mutasi berhasil dibuat', 'data' => $mutation]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $mutation = EmployeeMutation::with([
            'employee', 
            'oldCompany', 'oldBranch', 'oldDepartment', 'oldDivision', 'oldPosition',
            'newCompany', 'newBranch', 'newDepartment', 'newDivision', 'newPosition',
            'approvalRequests.logs.admin'
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $mutation]);
    }

    public function exportPdf($id)
    {
        $mutation = EmployeeMutation::with([
            'employee.primaryAssignment.position', 
            'employee.latestSignature',
            'oldCompany', 'oldBranch', 'oldDepartment', 'oldDivision', 'oldPosition', 'oldSupervisor.latestSignature',
            'newCompany', 'newBranch', 'newDepartment', 'newDivision', 'newPosition', 'newSupervisor.latestSignature',
            'creator.primaryAssignment.position',
            'creator.latestSignature'
        ])->findOrFail($id);

        $approvalRequest = \App\Models\ApprovalRequest::where('module', 'employee_mutations')
            ->where('module_id', $mutation->id)
            ->with(['workflow.steps', 'logs'])
            ->latest()
            ->first();

        $approverSteps = [];
        if ($approvalRequest && $approvalRequest->workflow && $approvalRequest->workflow->steps) {
            foreach ($approvalRequest->workflow->steps->sortBy('order') as $step) {
                $approverEmp = \App\Models\Employee::with(['primaryAssignment.position', 'primaryAssignment.department', 'latestSignature'])->find($step->approver_id);
                
                $log = $approvalRequest->logs->where('action', 'approve')->where('approval_step_id', $step->id)->first()
                    ?? $approvalRequest->logs->where('action', 'approve')->where('employee_id', $step->approver_id)->first();

                $isApproved = !is_null($log);
                $signatureBase64 = null;
                if ($isApproved && $approverEmp) {
                    $signatureBase64 = $approverEmp->signature_base64;
                }

                $approverSteps[] = [
                    'order' => $step->order,
                    'name' => $approverEmp?->name ?? 'Approver',
                    'nip' => $approverEmp?->employee_id ?? ($approverEmp?->nip ?? '-'),
                    'position' => $approverEmp?->primaryAssignment?->position?->name ?? 'Atasan / Manajemen',
                    'department' => $approverEmp?->primaryAssignment?->department?->name ?? '',
                    'is_approved' => $isApproved,
                    'approved_at' => $log && $log->created_at ? $log->created_at->format('d/m/Y H:i') : null,
                    'signature_base64' => $signatureBase64,
                ];
            }
        }

        $creatorSignature = $mutation->creator?->signature_base64;
        $employeeSignature = $mutation->employee?->signature_base64;

        $pdf = Pdf::loadView('pdf.employee_mutation', compact('mutation', 'approvalRequest', 'approverSteps', 'creatorSignature', 'employeeSignature'));
        $safeMemoNumber = str_replace(['/', '\\'], '_', $mutation->memo_number ?? $mutation->id);
        $filename = "SK_Mutasi_{$safeMemoNumber}.pdf";
        
        if (request()->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}

import os

controller_content = """<?php

namespace App\Http\Controllers\Api\\v1\Master;

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
        ]);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $mutations = $query->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $mutations]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:mutasi,promosi',
            'effective_date' => 'required|date',
            'new_company_id' => 'required|exists:companies,id',
            'new_department_id' => 'required|exists:departments,id',
            'new_position_id' => 'required|exists:positions,id',
            'approvers' => 'required|array|min:1',
        ]);

        try {
            DB::beginTransaction();

            $employee = Employee::with('assignments')->findOrFail($request->employee_id);
            $primaryAssignment = $employee->primaryAssignment() ?? $employee->assignments->first();

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

            // Dispatch Approval Request
            $approvalRequest = $this->approvalEngine->dispatch($moduleName, $mutation->id, auth()->id(), [
                'type' => $mutation->type,
                'effective_date' => $mutation->effective_date,
                'custom_workflow_id' => $workflow->id
            ]);
            
            // Override the workflow_id in approval request to force our custom workflow
            $approvalRequest->workflow_id = $workflow->id;
            $approvalRequest->save();

            // Note: In a real system, the dispatch function normally resolves workflow automatically. 
            // We manually override it here since we created a dedicated one.
            
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
            'employee', 
            'oldCompany', 'oldBranch', 'oldDepartment', 'oldDivision', 'oldPosition',
            'newCompany', 'newBranch', 'newDepartment', 'newDivision', 'newPosition'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.employee_mutation', compact('mutation'));
        
        return $pdf->download("Memo_{$mutation->type}_{$mutation->memo_number}.pdf");
    }
}
"""

with open('D:/Project/HRMS/backend/app/Http/Controllers/Api/v1/Master/EmployeeMutationController.php', 'w') as f:
    f.write(controller_content)

print("Controller created.")

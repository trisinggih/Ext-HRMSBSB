<?php

namespace App\Http\Controllers\Api\v1\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\StoreApprovalWorkflowRequest;
use App\Http\Requests\Approval\UpdateApprovalWorkflowRequest;
use App\Http\Requests\Approval\StoreApprovalStepRequest;
use App\Http\Resources\Approval\ApprovalRequestResource;
use App\Http\Resources\Approval\ApprovalWorkflowResource;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Services\ApprovalEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalEngineController extends Controller
{
    public function __construct(private ApprovalEngineService $engine)
    {
    }

    // ===== WORKFLOWS =====

    public function workflows(Request $request): JsonResponse
    {
        $workflows = ApprovalWorkflow::query()
            ->with('steps')
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('module')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar approval workflow',
            'data' => ApprovalWorkflowResource::collection($workflows)->response()->getData(true),
        ]);
    }

    public function storeWorkflow(StoreApprovalWorkflowRequest $request): JsonResponse
    {
        $workflow = ApprovalWorkflow::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Approval workflow berhasil dibuat',
            'data' => new ApprovalWorkflowResource($workflow->load('steps')),
        ], 201);
    }

    public function showWorkflow(ApprovalWorkflow $workflow): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail approval workflow',
            'data' => new ApprovalWorkflowResource($workflow->load('steps')),
        ]);
    }

    public function updateWorkflow(UpdateApprovalWorkflowRequest $request, ApprovalWorkflow $workflow): JsonResponse
    {
        $workflow->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Approval workflow berhasil diperbarui',
            'data' => new ApprovalWorkflowResource($workflow->load('steps')),
        ]);
    }

    public function destroyWorkflow(ApprovalWorkflow $workflow): JsonResponse
    {
        $workflow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Approval workflow berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===== STEPS =====

    public function steps(Request $request): JsonResponse
    {
        $steps = ApprovalStep::query()
            ->with('workflow')
            ->when($request->filled('workflow_id'), fn ($q) => $q->where('workflow_id', $request->workflow_id))
            ->orderBy('workflow_id')->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar approval step',
            'data' => \App\Http\Resources\Approval\ApprovalStepResource::collection($steps)->response()->getData(true),
        ]);
    }

    public function storeStep(StoreApprovalStepRequest $request): JsonResponse
    {
        $step = ApprovalStep::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Approval step berhasil dibuat',
            'data' => new \App\Http\Resources\Approval\ApprovalStepResource($step),
        ], 201);
    }

    public function destroyStep(ApprovalStep $step): JsonResponse
    {
        $step->delete();

        return response()->json([
            'success' => true,
            'message' => 'Approval step berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===== REQUESTS =====

    public function requests(Request $request): JsonResponse
    {
        $query = ApprovalRequest::query()
            ->with(['workflow.steps.approverEmployee', 'logs', 'requester'])
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'pending') {
                    $q->whereIn('status', ['pending', 'in_review']);
                } elseif (str_contains($request->status, ',')) {
                    $q->whereIn('status', explode(',', $request->status));
                } else {
                    $q->where('status', $request->status);
                }
            })
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('workflow_id'), fn ($q) => $q->where('workflow_id', $request->workflow_id))
            ->latest();

        // Notifikasi "approver" disimulasikan: admin list default pending/in_review.
        if (! $request->filled('status')) {
            $query->whereIn('status', ['pending', 'in_review']);
        }

        // Filter Multi-tier approval
        $user = $request->user();
        if ($user && $user->tokenCan('employee')) {
            $isPendingQuery = !$request->filled('status') || in_array($request->status, ['pending', 'in_review', 'pending,in_review']);

            $query->where(function ($q) use ($user, $isPendingQuery) {
                // Active approver check for current step
                $q->where(function ($activeQ) use ($user) {
                    // Condition A: Current step is a 'specific_user' matching this employee
                    $activeQ->whereExists(function ($query) use ($user) {
                        $query->select(\Illuminate\Support\Facades\DB::raw(1))
                              ->from('approval_steps')
                              ->whereColumn('approval_steps.workflow_id', 'approval_requests.workflow_id')
                              ->whereColumn('approval_steps.order', 'approval_requests.current_step')
                              ->where('approval_steps.approver_type', 'specific_user')
                              ->where('approval_steps.approver_id', $user->id);
                    })
                    // Condition B: Current step is 'direct_manager', 'supervisor', or 'manager' and this employee is the requester's supervisor
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereExists(function ($query) {
                            $query->select(\Illuminate\Support\Facades\DB::raw(1))
                                  ->from('approval_steps')
                                  ->whereColumn('approval_steps.workflow_id', 'approval_requests.workflow_id')
                                  ->whereColumn('approval_steps.order', 'approval_requests.current_step')
                                  ->whereIn('approval_steps.approver_type', ['direct_manager', 'supervisor', 'manager']);
                        })->where(function ($sub) use ($user) {
                            $sub->whereHas('requester.primaryAssignment', function ($assignQuery) use ($user) {
                                $assignQuery->where('supervisor_id', $user->id);
                            })->orWhereHas('requester.assignments', function ($assignQuery) use ($user) {
                                $assignQuery->where('supervisor_id', $user->id);
                            });
                        });
                    });
                });

                // Jika sedang melihat tab riwayat / approved / rejected, sertakan juga pengajuan yang pernah disetujui/ditolak oleh karyawan ini
                if (!$isPendingQuery) {
                    $q->orWhereExists(function ($query) use ($user) {
                        $query->select(\Illuminate\Support\Facades\DB::raw(1))
                              ->from('approval_logs')
                              ->whereColumn('approval_logs.approval_request_id', 'approval_requests.id')
                              ->where('approval_logs.employee_id', $user->id);
                    });
                }
            });
        }

        $approvals = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar approvaal request',
            'data' => ApprovalRequestResource::collection($approvals)->response()->getData(true),
        ]);
    }

    public function showRequest(ApprovalRequest $approval): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail approval request',
            'data' => new ApprovalRequestResource($approval->load(['workflow.steps.approverEmployee', 'logs', 'requester'])),
        ]);
    }

    public function approve(Request $request, ApprovalRequest $approval): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
            'tenor' => 'nullable|integer',
            'approved_item_ids' => 'nullable|array',
            'approved_item_ids.*' => 'integer',
        ]);

        if ($approval->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Approval request sudah disetujui sebelumnya.',
                'data' => null,
            ], 422);
        }
        if ($approval->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Approval request sudah ditolak sebelumnya.',
                'data' => null,
            ], 422);
        }

        $user = $request->user();
        $adminId = $user->tokenCan('admin') ? $user->id : null;
        $employeeId = $user->tokenCan('employee') ? $user->id : null;
        
        // Custom logic for Loan Request Tenor
        if ($approval->module === 'loan_request' && $request->filled('tenor')) {
            $loanReq = \App\Models\LoanRequest::find($approval->module_id);
            if ($loanReq) {
                $loanReq->update(['installment_months' => $request->integer('tenor')]);
            }
        }

        // Custom logic for Asset Request Multi-Items Approval
        if ($approval->module === 'asset_request' && $request->has('approved_item_ids')) {
            $approvedItemIds = array_filter(array_map('intval', (array) $request->input('approved_item_ids')));
            if (empty($approvedItemIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal pilih 1 item untuk disetujui, atau tolak pengajuan jika tidak ada item yang disetujui.',
                    'data' => null,
                ], 422);
            }

            $assetRequest = \App\Models\AssetRequest::find($approval->module_id);
            if ($assetRequest) {
                // Delete unapproved items from asset_request_items
                \App\Models\AssetRequestItem::where('asset_request_id', $assetRequest->id)
                    ->whereNotIn('id', $approvedItemIds)
                    ->delete();

                // Re-fetch remaining items
                $remainingItems = \App\Models\AssetRequestItem::where('asset_request_id', $assetRequest->id)->get();
                if ($remainingItems->isNotEmpty()) {
                    $totalQty = $remainingItems->sum('quantity');
                    $totalEst = $remainingItems->sum('estimated_price');
                    $firstItemName = $remainingItems->first()->item_name;
                    $summaryName = $remainingItems->count() > 1
                        ? ($firstItemName . ' (+' . ($remainingItems->count() - 1) . ' item lainnya)')
                        : $firstItemName;

                    $assetRequest->update([
                        'item_name' => $summaryName,
                        'quantity' => $totalQty > 0 ? $totalQty : 1,
                        'estimated_price' => $totalEst,
                    ]);

                    // Update approval payload so subsequent approvers immediately see only remaining items
                    $payload = $approval->payload ?? [];
                    $payload['item_name'] = $summaryName;
                    $payload['quantity'] = $totalQty > 0 ? $totalQty : 1;
                    $payload['estimated_price'] = (float) $totalEst;
                    $payload['unit_price'] = $totalQty > 0 ? (float) ($totalEst / $totalQty) : 0;
                    $payload['items'] = $remainingItems->map(fn($it) => [
                        'id' => $it->id,
                        'item_name' => $it->item_name,
                        'item_size' => $it->item_size,
                        'quantity' => $it->quantity,
                        'placement' => $it->placement,
                        'unit_price' => (float) ($it->unit_price ?: ($it->quantity > 0 ? $it->estimated_price / $it->quantity : 0)),
                        'estimated_price' => (float) $it->estimated_price,
                        'notes' => $it->notes,
                    ])->toArray();
                    $approval->payload = $payload;
                    $approval->save();
                }
            }
        }
        
        $this->engine->approve($approval, $adminId, $employeeId, $user->name, $request->note);

        return response()->json([
            'success' => true,
            'message' => 'Approval request disetujui.',
            'data' => new ApprovalRequestResource($approval->fresh()->load(['workflow', 'logs'])),
        ]);
    }

    public function reject(Request $request, ApprovalRequest $approval): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        if (in_array($approval->status, ['approved', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Approval request sudah memiliki status final.',
                'data' => null,
            ], 422);
        }

        $user = $request->user();
        $adminId = $user->tokenCan('admin') ? $user->id : null;
        $employeeId = $user->tokenCan('employee') ? $user->id : null;
        $this->engine->reject($approval, $adminId, $employeeId, $user->name, $request->note);

        return response()->json([
            'success' => true,
            'message' => 'Approval request ditolak.',
            'data' => new ApprovalRequestResource($approval->fresh()->load(['workflow', 'logs'])),
        ]);
    }

    public function cancel(Request $request, ApprovalRequest $approval): JsonResponse
    {
        if (in_array($approval->status, ['approved', 'rejected', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Approval request sudah memiliki status final.',
                'data' => null,
            ], 422);
        }

        $user = $request->user();
        if ($user->tokenCan('employee') && $approval->requested_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk membatalkan pengajuan ini.',
                'data' => null,
            ], 403);
        }

        $employeeId = $user->tokenCan('employee') ? $user->id : null;
        $this->engine->cancel($approval, $employeeId, $user->name, $request->note);

        return response()->json([
            'success' => true,
            'message' => 'Approval request dibatalkan.',
            'data' => new ApprovalRequestResource($approval->fresh()->load(['workflow', 'logs'])),
        ]);
    }

    public function destroyRequest(ApprovalRequest $approval): JsonResponse
    {
        $approval->delete();

        return response()->json([
            'success' => true,
            'message' => 'Approval request berhasil dihapus',
            'data' => null,
        ]);
    }
}

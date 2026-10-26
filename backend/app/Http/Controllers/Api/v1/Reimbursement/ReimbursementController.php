<?php

namespace App\Http\Controllers\Api\v1\Reimbursement;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class ReimbursementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Reimbursement::with(['employee', 'approver', 'approval.workflow.steps.approverEmployee']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reimbursements = $query->orderByDesc('date')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar Reimbursement',
            'data' => $reimbursements,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'attachment' => 'nullable', // can be file or string
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('reimbursements', 'public');
        }

        $validated['status'] = 'pending';

        $reimbursement = Reimbursement::create($validated);

        app(\App\Services\ApprovalEngineService::class)->dispatch('reimbursement', $reimbursement->id, $reimbursement->employee_id, [
            'date' => $reimbursement->date,
            'type' => $reimbursement->type,
            'amount' => $reimbursement->amount,
            'description' => $reimbursement->description,
            'attachment_url' => $reimbursement->attachment ? url('storage/' . $reimbursement->attachment) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reimbursement berhasil diajukan',
            'data' => $reimbursement->load(['employee', 'approval']),
        ], 201);
    }

    public function show(Reimbursement $reimbursement): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $reimbursement->load(['employee', 'approver']),
        ]);
    }

    public function update(Request $request, Reimbursement $reimbursement): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'sometimes|date',
            'type' => 'sometimes|string|max:50',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'attachment' => 'nullable', // can be file or string
        ]);

        if ($request->hasFile('attachment')) {
            if ($reimbursement->attachment) {
                Storage::disk('public')->delete($reimbursement->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('reimbursements', 'public');
        } else {
            // If it's empty but not a file, it might have been cleared
            if (array_key_exists('attachment', $validated) && empty($validated['attachment']) && $reimbursement->attachment) {
                Storage::disk('public')->delete($reimbursement->attachment);
            }
        }

        $reimbursement->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reimbursement berhasil diupdate',
            'data' => $reimbursement,
        ]);
    }

    public function destroy(Reimbursement $reimbursement): JsonResponse
    {
        if ($reimbursement->attachment) {
            Storage::disk('public')->delete($reimbursement->attachment);
        }
        $reimbursement->delete();
        return response()->json([
            'success' => true,
            'message' => 'Reimbursement berhasil dihapus'
        ]);
    }

    public function approve(Request $request, Reimbursement $reimbursement): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $reimbursement->update([
            'status' => $request->status,
            'approved_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reimbursement berhasil di-' . $request->status,
            'data' => $reimbursement
        ]);
    }

    public function myIndex(Request $request): JsonResponse
    {
        $query = Reimbursement::with(['approver', 'approval.workflow.steps.approverEmployee', 'approval.logs'])
            ->where('employee_id', Auth::id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reimbursements = $query->orderByDesc('date')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar Reimbursement Saya',
            'data' => $reimbursements,
        ]);
    }

    public function myStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'attachment' => 'nullable', // can be file or string
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('reimbursements', 'public');
        }

        $validated['employee_id'] = Auth::id();
        $validated['status'] = 'pending';

        $reimbursement = Reimbursement::create($validated);

        app(\App\Services\ApprovalEngineService::class)->dispatch('reimbursement', $reimbursement->id, $reimbursement->employee_id, [
            'date' => $reimbursement->date,
            'type' => $reimbursement->type,
            'amount' => $reimbursement->amount,
            'description' => $reimbursement->description,
            'attachment_url' => $reimbursement->attachment ? url('storage/' . $reimbursement->attachment) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reimbursement berhasil diajukan',
            'data' => $reimbursement->load('approval'),
        ], 201);
    }
}

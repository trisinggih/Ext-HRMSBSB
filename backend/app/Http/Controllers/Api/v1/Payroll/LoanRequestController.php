<?php

namespace App\Http\Controllers\Api\v1\Payroll;

use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use App\Services\ApprovalEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LoanRequestController extends Controller
{
    public function __construct(private ApprovalEngineService $engine)
    {
    }

    /** GET /loan/request */
    public function index(Request $request): JsonResponse
    {
        $requests = LoanRequest::with(['approval', 'approval.workflow.steps', 'approval.logs', 'approval.requester'])
            ->where('employee_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true, 
            'message' => 'Daftar pengajuan kasbon', 
            'data' => $requests,
        ]);
    }

    /** POST /loan/request */
    public function store(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'amount' => 'required|numeric|min:1',
            'installment_months' => 'nullable|integer|min:0',
            'reason' => 'required|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        $emp = $request->user();
        $payrollSetting = $emp->payrollSettings;
        $maxLimit = $payrollSetting?->max_loan_limit;

        if ($maxLimit && $maxLimit > 0) {
            // Check active loans remaining balance
            $activeLoans = \App\Models\EmployeeLoan::where('employee_id', $emp->id)
                ->where('status', 'active')
                ->sum('remaining_balance');
            
            // Check pending requests
            $pendingRequests = LoanRequest::where('employee_id', $emp->id)
                ->where('status', 'pending')
                ->sum('amount');

            $availableLimit = $maxLimit - $activeLoans - $pendingRequests;

            if ($valid['amount'] > $availableLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nominal pengajuan melebihi sisa limit kasbon Anda. Sisa limit: Rp ' . number_format($availableLimit, 0, ',', '.'),
                    'data' => null,
                ], 422);
            }
        }
        
        $attachmentUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('loans', 'public');
            $attachmentUrl = url('storage/' . $path);
        }

        $data = [
            'employee_id' => $emp->id,
            'date' => Carbon::now()->toDateString(),
            'amount' => $valid['amount'],
            'installment_months' => $valid['installment_months'] ?? 0,
            'reason' => $valid['reason'],
            'status' => 'pending',
            'attachment_url' => $attachmentUrl,
        ];

        $loanRequest = LoanRequest::create($data);
        
        $this->engine->dispatch('loan_request', $loanRequest->id, $emp->id, [
            'amount' => $loanRequest->amount,
            'installment_months' => $loanRequest->installment_months,
            'date' => $loanRequest->date,
            'attachment_url' => $attachmentUrl,
        ]);

        \App\Services\ActivityLogService::log(
            $emp, 
            'create_loan', 
            "{$emp->name} mengajukan kasbon sebesar Rp" . number_format($loanRequest->amount, 0, ',', '.') . " pada tanggal " . \Carbon\Carbon::parse($loanRequest->date)->translatedFormat('d F Y'), 
            'Pengajuan Kasbon', 
            $loanRequest
        );

        return response()->json([
            'success' => true, 
            'message' => 'Permohonan kasbon dikirim', 
            'data' => $loanRequest,
        ], 201);
    }
}

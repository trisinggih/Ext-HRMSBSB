<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalLog;
use App\Models\EmployeeLoan;
use App\Models\LoanRequest;
use App\Models\PayrollDetail;
use App\Services\ApprovalEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLoanRequestController extends Controller
{
    public function __construct(private ApprovalEngineService $engine)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $requests = LoanRequest::query()
            ->with([
                'employee:id,employee_id,name',
                'approval.workflow.steps.approverEmployee:id,name',
                'approval.logs',
                'loan',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 50));

        // Tambahkan kalkulasi cicilan dan riwayat penarikan gaji
        $requests->getCollection()->transform(function ($item) {
            $tenor = max(1, (int) $item->installment_months);
            $amount = (float) $item->amount;
            $installmentAmount = round($amount / $tenor, 2);

            if ($item->loan) {
                $installmentAmount = (float) $item->loan->installment_amount;
                $remainingBalance = (float) $item->loan->remaining_balance;
                $totalPaid = max(0, (float) $item->loan->total_loan - $remainingBalance);
                $loanStatus = $item->loan->status;
            } else {
                $remainingBalance = $item->status === 'approved' ? 0 : $amount;
                $totalPaid = $item->status === 'approved' ? $amount : 0;
                $loanStatus = $item->status;
            }

            // Ambil potongan di payroll untuk karyawan ini
            $repayments = PayrollDetail::whereHas('run', function ($q) use ($item) {
                    $q->where('employee_id', $item->employee_id);
                })
                ->where(function ($q) {
                    $q->where('payroll_component_id', 12)
                      ->orWhere('label', 'like', '%kasbon%')
                      ->orWhere('label', 'like', '%pinjaman%');
                })
                ->with(['run.period'])
                ->latest()
                ->get()
                ->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'payroll_run_id' => $detail->payroll_run_id,
                        'period_name' => $detail->run?->period?->name ?? 'Periode Payroll',
                        'period_code' => $detail->run?->period?->period,
                        'deduction_amount' => (float) $detail->amount,
                        'status' => $detail->run?->status ?? 'paid',
                        'paid_at' => $detail->run?->period?->payment_date ?? $detail->run?->created_at,
                        'label' => $detail->label,
                    ];
                });

            if ($repayments->isNotEmpty()) {
                $payrollPaid = (float) $repayments->sum('deduction_amount');
                if ($payrollPaid > 0) {
                    $totalPaid = $payrollPaid;
                    $remainingBalance = max(0, $amount - $totalPaid);
                }
            }

            $item->installment_amount = $installmentAmount;
            $item->total_paid = $totalPaid;
            $item->remaining_balance = $remainingBalance;
            $item->repayments = $repayments;
            $item->loan_status = $loanStatus;

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan kasbon (Admin)',
            'data' => $requests,
        ]);
    }

    public function updateInstallment(Request $request, LoanRequest $loanRequest): JsonResponse
    {
        $validated = $request->validate([
            'installment_months' => 'required|integer|min:1|max:60',
            'installment_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $newTenor = (int) $validated['installment_months'];
        $amount = (float) $loanRequest->amount;
        $newInstallmentAmount = !empty($validated['installment_amount']) && $validated['installment_amount'] > 0
            ? (float) $validated['installment_amount']
            : round($amount / $newTenor, 2);

        $oldTenor = $loanRequest->installment_months;

        $loanRequest->update([
            'installment_months' => $newTenor,
        ]);

        // Jika sudah ada employee_loan
        $loan = $loanRequest->loan ?? EmployeeLoan::where('employee_id', $loanRequest->employee_id)->where('status', 'active')->first();
        if ($loan) {
            $loan->update([
                'installment_amount' => $newInstallmentAmount,
            ]);
        }

        // Catat di approval log jika approval workflow ada
        if ($loanRequest->approval) {
            ApprovalLog::create([
                'approval_request_id' => $loanRequest->approval->id,
                'admin_id' => $request->user()?->id,
                'approver_name' => $request->user()?->name ?? 'HR Admin',
                'action' => 'revise',
                'note' => "Tenor cicilan diubah dari {$oldTenor} bulan menjadi {$newTenor} bulan (Rp " . number_format($newInstallmentAmount, 0, ',', '.') . "/bln)" . (!empty($validated['note']) ? ': ' . $validated['note'] : ''),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cicilan kasbon berhasil diperbarui.',
            'data' => $loanRequest->fresh(['loan', 'employee:id,employee_id,name', 'approval.logs']),
        ]);
    }

    public function repayments(LoanRequest $loanRequest): JsonResponse
    {
        $repayments = PayrollDetail::whereHas('run', function ($q) use ($loanRequest) {
                $q->where('employee_id', $loanRequest->employee_id);
            })
            ->where(function ($q) {
                $q->where('payroll_component_id', 12)
                  ->orWhere('label', 'like', '%kasbon%')
                  ->orWhere('label', 'like', '%pinjaman%');
            })
            ->with(['run.period'])
            ->latest()
            ->get()
            ->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'payroll_run_id' => $detail->payroll_run_id,
                    'period_name' => $detail->run?->period?->name ?? 'Periode Payroll',
                    'period_code' => $detail->run?->period?->period,
                    'period_start' => $detail->run?->period?->start_date,
                    'period_end' => $detail->run?->period?->end_date,
                    'deduction_amount' => (float) $detail->amount,
                    'status' => $detail->run?->status ?? 'paid',
                    'paid_at' => $detail->run?->period?->payment_date ?? $detail->run?->created_at,
                    'label' => $detail->label,
                ];
            });

        $tenor = max(1, (int) $loanRequest->installment_months);
        $amount = (float) $loanRequest->amount;
        $installmentAmount = round($amount / $tenor, 2);

        if ($loanRequest->loan) {
            $installmentAmount = (float) $loanRequest->loan->installment_amount;
            $remainingBalance = (float) $loanRequest->loan->remaining_balance;
            $totalPaid = max(0, (float) $loanRequest->loan->total_loan - $remainingBalance);
            $loanStatus = $loanRequest->loan->status;
        } else {
            $totalPaid = (float) $repayments->sum('deduction_amount');
            $remainingBalance = max(0, $amount - $totalPaid);
            $loanStatus = $loanRequest->status;
        }

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pembayaran kasbon dari gaji',
            'data' => [
                'loan_request_id' => $loanRequest->id,
                'employee' => $loanRequest->employee,
                'total_loan' => $amount,
                'installment_months' => $tenor,
                'installment_amount' => $installmentAmount,
                'total_paid' => $totalPaid,
                'remaining_balance' => $remainingBalance,
                'loan_status' => $loanStatus,
                'repayments' => $repayments,
            ],
        ]);
    }

    public function approve(Request $request, LoanRequest $loanRequest): JsonResponse
    {
        if ($loanRequest->status !== 'pending' && $loanRequest->status !== 'in_review') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan ini tidak dapat disetujui (status: ' . $loanRequest->status . ')',
                'data' => null,
            ], 422);
        }

        $approval = $loanRequest->approval;
        if (!$approval) {
            return response()->json([
                'success' => false,
                'message' => 'Data approval tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        // Jika admin mengubah tenor cicilan saat approval
        if ($request->filled('installment_months') && $request->integer('installment_months') > 0) {
            $loanRequest->update([
                'installment_months' => $request->integer('installment_months'),
            ]);
        }

        $admin = $request->user();
        $this->engine->approve($approval, $admin->id, $admin->name, $request->note ?? 'Disetujui oleh HR');

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan kasbon berhasil disetujui.',
            'data' => $loanRequest->fresh(['approval.workflow.steps.approverEmployee:id,name', 'approval.logs', 'employee:id,employee_id,name', 'loan']),
        ]);
    }

    public function reject(Request $request, LoanRequest $loanRequest): JsonResponse
    {
        if ($loanRequest->status !== 'pending' && $loanRequest->status !== 'in_review') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan ini tidak dapat ditolak (status: ' . $loanRequest->status . ')',
                'data' => null,
            ], 422);
        }

        $approval = $loanRequest->approval;
        if (!$approval) {
            return response()->json([
                'success' => false,
                'message' => 'Data approval tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $admin = $request->user();
        $this->engine->reject($approval, $admin->id, $admin->name, $request->note ?? 'Ditolak oleh HR');

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan kasbon berhasil ditolak.',
            'data' => $loanRequest->fresh(['approval.workflow.steps.approverEmployee:id,name', 'approval.logs', 'employee:id,employee_id,name']),
        ]);
    }
}


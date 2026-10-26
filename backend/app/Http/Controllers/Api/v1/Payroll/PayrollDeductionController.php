<?php

namespace App\Http\Controllers\Api\v1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollDeduction\StorePayrollDeductionRequest;
use App\Http\Requests\PayrollDeduction\UpdatePayrollDeductionRequest;
use App\Http\Resources\PayrollDeduction\PayrollDeductionResource;
use App\Models\PayrollDeduction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PayrollDeductionController extends Controller
{
    /** GET /api/v1/payroll-deductions */
    public function index(Request $request): JsonResponse
    {
        $query = PayrollDeduction::query()->with([
            'employee:id,employee_id,name',
            'payrollPeriod:id,name,period,start_date,end_date',
        ]);

        if ($request->has('employee_id') && $request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->has('payroll_period_id') && $request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', $request->integer('payroll_period_id'));
        }

        if ($request->has('type') && $request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('q') && $request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQ) use ($search) {
                      $empQ->where('name', 'like', "%{$search}%")
                           ->orWhere('employee_id', 'like', "%{$search}%");
                  });
            });
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar potongan lain-lain',
            'data' => [
                'items' => PayrollDeductionResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /** POST /api/v1/payroll-deductions */
    public function store(StorePayrollDeductionRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('payroll_deductions', 'public');
        } else {
            unset($data['attachment']);
        }

        $deduction = PayrollDeduction::create($data);
        $deduction->load(['employee:id,employee_id,name', 'payrollPeriod:id,name,period,start_date,end_date']);

        return response()->json([
            'success' => true,
            'message' => 'Potongan lain-lain berhasil dibuat',
            'data' => new PayrollDeductionResource($deduction),
        ], 201);
    }

    /** GET /api/v1/payroll-deductions/{payrollDeduction} */
    public function show(PayrollDeduction $payrollDeduction): JsonResponse
    {
        $payrollDeduction->load(['employee:id,employee_id,name', 'payrollPeriod:id,name,period,start_date,end_date']);

        return response()->json([
            'success' => true,
            'message' => 'Detail potongan lain-lain',
            'data' => new PayrollDeductionResource($payrollDeduction),
        ]);
    }

    /** PUT /api/v1/payroll-deductions/{payrollDeduction} */
    public function update(UpdatePayrollDeductionRequest $request, PayrollDeduction $payrollDeduction): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('attachment')) {
            $new = $request->file('attachment')->store('payroll_deductions', 'public');
            if ($payrollDeduction->attachment) {
                Storage::disk('public')->delete($payrollDeduction->attachment);
            }
            $data['attachment'] = $new;
        } else {
            unset($data['attachment']);
        }

        $payrollDeduction->update($data);
        $payrollDeduction->load(['employee:id,employee_id,name', 'payrollPeriod:id,name,period,start_date,end_date']);

        return response()->json([
            'success' => true,
            'message' => 'Potongan lain-lain berhasil diperbarui',
            'data' => new PayrollDeductionResource($payrollDeduction),
        ]);
    }

    /** DELETE /api/v1/payroll-deductions/{payrollDeduction} */
    public function destroy(PayrollDeduction $payrollDeduction): JsonResponse
    {
        if ($payrollDeduction->attachment) {
            Storage::disk('public')->delete($payrollDeduction->attachment);
        }

        $payrollDeduction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Potongan lain-lain berhasil dihapus',
            'data' => null,
        ]);
    }

    /** GET /api/v1/my/deductions (Karyawan melihat potongannya sendiri) */
    public function myIndex(Request $request): JsonResponse
    {
        $employee = $request->user();
        if (!$employee || !($employee instanceof \App\Models\Employee)) {
            $employee = $request->user()?->employee;
        }

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan',
            ], 404);
        }

        $items = PayrollDeduction::where('employee_id', $employee->id)
            ->with(['payrollPeriod:id,name,period,start_date,end_date'])
            ->orderByDesc('deduction_date')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar potongan saya',
            'data' => [
                'items' => PayrollDeductionResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\v1\Phase6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Termination\StoreTerminationRequest;
use App\Http\Requests\Termination\UpdateTerminationRequest;
use App\Http\Resources\Termination\TerminationResource;
use App\Models\EmployeeTermination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TerminationController extends Controller
{
    /** GET /admin /terminations?employee_id= */
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeTermination::query()
            ->with(['employee:id,employee_id,name,is_active', 'employee.primaryAssignment', 'clearances']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar terminasi',
            'data' => [
                'items' => TerminationResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /** POST /admin /terminations */
    public function store(StoreTerminationRequest $request): JsonResponse
    {
        $termination = EmployeeTermination::create($request->validated());

        // Langsung set status karyawan menjadi nonaktif
        if ($termination->employee) {
            $termination->employee->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terminasi berhasil dibuat dan karyawan telah dinonaktifkan',
            'data' => new TerminationResource($termination->load(['employee:id,employee_id,name,is_active', 'employee.primaryAssignment'])),
        ], 201);
    }

    /** GET /admin /terminations/{termination} */
    public function show(EmployeeTermination $termination): JsonResponse
    {
        $termination->load(['employee:id,employee_id,name,is_active', 'employee.primaryAssignment', 'clearances']);

        return response()->json([
            'success' => true,
            'message' => 'Detail terminasi',
            'data' => new TerminationResource($termination),
        ]);
    }

    /** PUT /admin /terminations/{termination} */
    public function update(UpdateTerminationRequest $request, EmployeeTermination $termination): JsonResponse
    {
        $termination->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Terminasi berhasil diperbarui',
            'data' => new TerminationResource($termination->fresh(['employee', 'clearances'])),
        ]);
    }

    /** DELETE /admin /terminations/{termination} */
    public function destroy(EmployeeTermination $termination): JsonResponse
    {
        $termination->delete();

        return response()->json([
            'success' => true,
            'message' => 'Terminasi berhasil dihapus',
            'data' => null,
        ]);
    }

    /** POST /admin /terminations/{termination}/approve — setujui terminasi */
    public function approve(EmployeeTermination $termination): JsonResponse
    {
        if ($termination->status === 'approved' || $termination->status === 'completed') {
            throw ValidationException::withMessages(['status' => 'Terminasi sudah disetujui.']);
        }

        $termination->update(['status' => 'approved']);

        // Tandai employee nonaktif (employment_status enum tidak memuat 'terminated',
        // maka pakai is_active=false sebagai penanda terminasi).
        $employee = $termination->employee;
        if ($employee) {
            $employee->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terminasi disetujui, status karyawan dinonaktifkan',
            'data' => new TerminationResource($termination->fresh(['employee', 'clearances'])),
        ]);
    }

    // ===================== EMPLOYEE (data sendiri) =====================

    /** GET /employee /terminations */
    public function myIndex(Request $request): JsonResponse
    {
        $emp = $request->user();
        $items = EmployeeTermination::with('clearances')
            ->where('employee_id', $emp->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat terminasi saya',
            'data' => TerminationResource::collection($items)->response()->getData(true),
        ]);
    }

    /** GET /admin/terminations/{termination}/pdf */
    public function downloadPdf(EmployeeTermination $termination)
    {
        $termination->load(['employee.primaryAssignment.department', 'employee.primaryAssignment.position', 'employee.latestSignature', 'clearances']);
        $company = \App\Models\Company::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.termination', compact('termination', 'company'))
            ->setPaper('A4', 'portrait');

        $empName = str_replace(' ', '_', $termination->employee->name ?? 'Karyawan');
        $filename = 'Surat_Terminasi_' . $empName . '_' . ($termination->termination_date ? $termination->termination_date->format('Ymd') : date('Ymd')) . '.pdf';

        return $pdf->download($filename);
    }
}

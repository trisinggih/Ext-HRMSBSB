<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Company;
use App\Models\CompanyNumbering;
use Illuminate\Http\JsonResponse;

class CompanyNumberingController extends Controller
{
    public function index(Company $company): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar pengaturan penomoran perusahaan',
            'data' => $company->numberings->keyBy('type'),
        ]);
    }

    public function store(Request $request, Company $company): JsonResponse
    {
        $data = $request->validate([
            'numberings' => 'required|array',
            'numberings.*.type' => 'required|string',
            'numberings.*.format' => 'required|string',
            'numberings.*.counter' => 'required|integer|min:1',
        ]);

        foreach ($data['numberings'] as $item) {
            CompanyNumbering::updateOrCreate(
                ['company_id' => $company->id, 'type' => $item['type']],
                ['format' => $item['format'], 'counter' => $item['counter']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan penomoran berhasil disimpan',
            'data' => $company->numberings()->get()->keyBy('type'),
        ]);
    }
}

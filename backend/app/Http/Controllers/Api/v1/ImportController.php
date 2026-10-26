<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GenericImport;

class ImportController extends Controller
{
    public function import(Request $request, $module)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        $modelClass = $this->getModelClass($module);
        if (!$modelClass) {
            return response()->json(['success' => false, 'message' => "Modul {$module} tidak mendukung import"], 400);
        }

        $extraData = $request->except('file');

        try {
            Excel::import(new GenericImport($modelClass, $extraData), $request->file('file'));
            return response()->json(['success' => true, 'message' => 'Data berhasil diimport']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengimport data: ' . $e->getMessage()], 500);
        }
    }

    private function getModelClass($module)
    {
        $map = [
            'departments' => \App\Models\Department::class,
            'branches' => \App\Models\Branch::class,
            'companies' => \App\Models\Company::class,
            'divisions' => \App\Models\Division::class,
            'positions' => \App\Models\Position::class,
            'employees' => \App\Models\Employee::class,
            'leave-types' => \App\Models\LeaveType::class,
            'shifts' => \App\Models\Shift::class,
        ];
        return $map[$module] ?? null;
    }
}

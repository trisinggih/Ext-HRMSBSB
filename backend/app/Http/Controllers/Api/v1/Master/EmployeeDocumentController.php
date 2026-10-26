<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    public function index($employee)
    {
        return response()->json(EmployeeDocument::where('employee_id', $employee)->get());
    }

    public function store(Request $request, $employee)
    {
        $request->validate([
            'document_type' => 'required|string',
            'file' => 'required|file|max:5120'
        ]);

        $file = $request->file('file');
        $path = $file->store('employee_documents', 'public');

        $doc = EmployeeDocument::create([
            'employee_id' => $employee,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize()
        ]);

        return response()->json($doc);
    }

    public function destroy($employee, $id)
    {
        $doc = EmployeeDocument::where('employee_id', $employee)->findOrFail($id);
        if ($doc->file_path) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

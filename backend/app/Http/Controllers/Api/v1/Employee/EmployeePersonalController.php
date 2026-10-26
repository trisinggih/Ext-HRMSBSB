<?php

namespace App\Http\Controllers\Api\v1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\EmployeeFamily;
use App\Models\EmployeeEducation;
use App\Models\EmployeeDocument;
use App\Models\EmployeeCertification;
use Illuminate\Support\Facades\Storage;

class EmployeePersonalController extends Controller
{
    // === FAMILY ===
    public function getFamily(Request $request): JsonResponse
    {
        $employee = $request->user();
        $families = EmployeeFamily::where('employee_id', $employee->id)->get();
        return response()->json(['success' => true, 'data' => $families]);
    }

    public function storeFamily(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'relation' => 'required|string',
            'phone' => 'nullable|string',
            'gender' => 'required|string',
            'dob' => 'required|date',
            'profession' => 'nullable|string',
            'address' => 'nullable|string',
            'is_emergency_contact' => 'nullable|boolean'
        ]);

        $employee = $request->user();
        $data = $request->all();
        $data['employee_id'] = $employee->id;
        $data['is_emergency_contact'] = $request->boolean('is_emergency_contact');
        if (isset($data['dob']) && !isset($data['birth_date'])) {
            $data['birth_date'] = $data['dob'];
        }
        if (isset($data['profession']) && !isset($data['occupation'])) {
            $data['occupation'] = $data['profession'];
        }
        if (isset($data['address']) && !isset($data['residential_address'])) {
            $data['residential_address'] = $data['address'];
        }

        $family = EmployeeFamily::create($data);
        return response()->json(['success' => true, 'message' => 'Data keluarga berhasil ditambahkan', 'data' => $family]);
    }

    public function updateFamily(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'relation' => 'required|string',
            'phone' => 'nullable|string',
            'gender' => 'required|string',
            'dob' => 'required|date',
            'profession' => 'nullable|string',
            'address' => 'nullable|string',
            'is_emergency_contact' => 'nullable|boolean'
        ]);

        $family = EmployeeFamily::where('employee_id', $request->user()->id)->findOrFail($id);
        
        $data = $request->all();
        $data['is_emergency_contact'] = $request->boolean('is_emergency_contact');
        if (isset($data['dob']) && !isset($data['birth_date'])) {
            $data['birth_date'] = $data['dob'];
        }
        if (isset($data['profession']) && !isset($data['occupation'])) {
            $data['occupation'] = $data['profession'];
        }
        if (isset($data['address']) && !isset($data['residential_address'])) {
            $data['residential_address'] = $data['address'];
        }
        $family->update($data);

        return response()->json(['success' => true, 'message' => 'Data keluarga berhasil diperbarui', 'data' => $family]);
    }

    public function deleteFamily(Request $request, $id): JsonResponse
    {
        $family = EmployeeFamily::where('employee_id', $request->user()->id)->findOrFail($id);
        $family->delete();
        return response()->json(['success' => true, 'message' => 'Data keluarga berhasil dihapus']);
    }

    // === EDUCATION ===
    public function getEducation(Request $request): JsonResponse
    {
        $employee = $request->user();
        $educations = EmployeeEducation::where('employee_id', $employee->id)->get();
        return response()->json(['success' => true, 'data' => $educations]);
    }

    public function storeEducation(Request $request): JsonResponse
    {
        $request->validate([
            'level' => 'required|string',
            'institution_name' => 'required|string',
            'major' => 'nullable|string',
            'start_year' => 'nullable|integer',
            'end_year' => 'nullable|integer',
            'gpa' => 'nullable|numeric'
        ]);

        $employee = $request->user();
        $data = $request->all();
        $data['employee_id'] = $employee->id;

        $education = EmployeeEducation::create($data);
        return response()->json(['success' => true, 'message' => 'Data pendidikan berhasil ditambahkan', 'data' => $education]);
    }

    public function updateEducation(Request $request, $id): JsonResponse
    {
        $request->validate([
            'level' => 'required|string',
            'institution_name' => 'required|string',
            'major' => 'nullable|string',
            'start_year' => 'nullable|integer',
            'end_year' => 'nullable|integer',
            'gpa' => 'nullable|numeric'
        ]);

        $education = EmployeeEducation::where('employee_id', $request->user()->id)->findOrFail($id);
        $education->update($request->all());

        return response()->json(['success' => true, 'message' => 'Data pendidikan berhasil diperbarui', 'data' => $education]);
    }

    public function deleteEducation(Request $request, $id): JsonResponse
    {
        $education = EmployeeEducation::where('employee_id', $request->user()->id)->findOrFail($id);
        $education->delete();
        return response()->json(['success' => true, 'message' => 'Data pendidikan berhasil dihapus']);
    }

    // === CERTIFICATION ===
    public function getCertification(Request $request): JsonResponse
    {
        $employee = $request->user();
        $certifications = EmployeeCertification::where('employee_id', $employee->id)->get();
        return response()->json(['success' => true, 'data' => $certifications]);
    }

    public function storeCertification(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'issuer' => 'nullable|string',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'credential_id' => 'nullable|string',
            'credential_url' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $employee = $request->user();
        $data = $request->all();
        $data['employee_id'] = $employee->id;

        $certification = EmployeeCertification::create($data);
        return response()->json(['success' => true, 'message' => 'Data sertifikasi berhasil ditambahkan', 'data' => $certification]);
    }

    public function updateCertification(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'issuer' => 'nullable|string',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'credential_id' => 'nullable|string',
            'credential_url' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $certification = EmployeeCertification::where('employee_id', $request->user()->id)->findOrFail($id);
        $certification->update($request->all());

        return response()->json(['success' => true, 'message' => 'Data sertifikasi berhasil diperbarui', 'data' => $certification]);
    }

    public function deleteCertification(Request $request, $id): JsonResponse
    {
        $certification = EmployeeCertification::where('employee_id', $request->user()->id)->findOrFail($id);
        $certification->delete();
        return response()->json(['success' => true, 'message' => 'Data sertifikasi berhasil dihapus']);
    }

    // === DOCUMENT ===
    public function getDocument(Request $request): JsonResponse
    {
        $employee = $request->user();
        $documents = EmployeeDocument::where('employee_id', $employee->id)->get();
        return response()->json(['success' => true, 'data' => $documents]);
    }

    public function storeDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120' // Max 5MB
        ]);

        $employee = $request->user();
        $file = $request->file('file');

        $path = $file->store('employee_documents', 'public');

        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => $request->document_type,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize()
        ]);

        return response()->json(['success' => true, 'message' => 'Dokumen berhasil diunggah', 'data' => $document]);
    }

    public function deleteDocument(Request $request, $id): JsonResponse
    {
        $document = EmployeeDocument::where('employee_id', $request->user()->id)->findOrFail($id);
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();
        return response()->json(['success' => true, 'message' => 'Dokumen berhasil dihapus']);
    }
}

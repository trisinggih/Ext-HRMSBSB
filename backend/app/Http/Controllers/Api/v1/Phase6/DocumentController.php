<?php

namespace App\Http\Controllers\Api\v1\Phase6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Resources\Document\DocumentResource;
use App\Models\EmployeeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    /** GET /admin /documents?employee_id= — list dokumen */
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeDocument::query()->with('employee:id,employee_id,name');

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        $items = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar dokumen',
            'data' => [
                'items' => DocumentResource::collection($items)->response()->getData(true)['data'],
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /** POST /admin /documents */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['employee_id'])) {
            throw ValidationException::withMessages(['employee_id' => 'employee_id wajib diisi.']);
        }

        $doc = $this->createRecord($data, $request);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diunggah',
            'data' => new DocumentResource($doc),
        ], 201);
    }

    /** GET /admin /documents/{document} */
    public function show(EmployeeDocument $document): JsonResponse
    {
        $document->load('employee:id,employee_id,name');

        return response()->json([
            'success' => true,
            'message' => 'Detail dokumen',
            'data' => new DocumentResource($document),
        ]);
    }

    /** PUT /admin /documents/{document} */
    public function update(UpdateDocumentRequest $request, EmployeeDocument $document): JsonResponse
    {
        $data = [];
        if ($request->has('document_type')) {
            $data['document_type'] = $request->input('document_type');
        }
        $this->saveFileIfAny($request, $data, $document);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diperbarui',
            'data' => new DocumentResource($document->fresh()),
        ]);
    }

    /** DELETE /admin /documents/{document} */
    public function destroy(EmployeeDocument $document): JsonResponse
    {
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===================== EMPLOYEE (data sendiri) =====================

    /** GET /employee /documents — dokumen milik sendiri */
    public function myIndex(Request $request): JsonResponse
    {
        $emp = $request->user();
        $items = EmployeeDocument::where('employee_id', $emp->id)->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen saya',
            'data' => DocumentResource::collection($items)->response()->getData(true),
        ]);
    }

    /** POST /employee /documents */
    public function myStore(StoreDocumentRequest $request): JsonResponse
    {
        $emp = $request->user();
        $data = array_merge($request->validated(), ['employee_id' => $emp->id]);
        $doc = $this->createRecord($data, $request);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diunggah',
            'data' => new DocumentResource($doc),
        ], 201);
    }

    /** PUT /employee /documents/{document} */
    public function myUpdate(UpdateDocumentRequest $request, EmployeeDocument $document): JsonResponse
    {
        $this->ensureOwner($document, $request->user()->id);
        $data = [];
        if ($request->has('document_type')) {
            $data['document_type'] = $request->input('document_type');
        }
        $this->saveFileIfAny($request, $data, $document);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diperbarui',
            'data' => new DocumentResource($document->fresh()),
        ]);
    }

    /** DELETE /employee /documents/{document} */
    public function myDestroy(Request $request, EmployeeDocument $document): JsonResponse
    {
        $this->ensureOwner($document, $request->user()->id);
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus',
            'data' => null,
        ]);
    }

    // ===================== Helper =====================

    private function createRecord(array $data, Request $request): EmployeeDocument
    {
        $file = $request->file('file');

        $path = $file->store('documents', 'public');

        $record = EmployeeDocument::create([
            'employee_id' => $data['employee_id'],
            'document_type' => $data['document_type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return $record;
    }

    private function saveFileIfAny(Request $request, array &$extra, EmployeeDocument $document): void
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extra['file_path'] = $file->store('documents', 'public');
            $extra['file_name'] = $file->getClientOriginalName();
            $extra['mime_type'] = $file->getMimeType();
            $extra['file_size'] = $file->getSize();

            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
        }

        if (!empty($extra)) {
            $document->update($extra);
        }
    }

    private function ensureOwner(EmployeeDocument $document, int $employeeId): void
    {
        if ($document->employee_id !== $employeeId) {
            throw ValidationException::withMessages(['data' => 'Anda tidak memiliki akses ke data ini.']);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\v1\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSignature;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeSignatureController extends Controller
{
    /**
     * Dapatkan tanda tangan aktif/terakhir milik karyawan.
     */
    public function getSignature(Request $request): JsonResponse
    {
        $employee = $request->user();
        $signature = EmployeeSignature::where('employee_id', $employee->id)
            ->latest('id')
            ->first();

        if (!$signature) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada tanda tangan yang disimpan',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tanda tangan berhasil dimuat',
            'data' => [
                'id' => $signature->id,
                'signature_url' => $signature->signature_url,
                'strokes_data' => $signature->strokes_data,
                'created_at' => $signature->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Simpan tanda tangan baru (free-form canvas) milik karyawan.
     * Menerima base64 data URL atau unggahan file multipart.
     */
    public function saveSignature(Request $request): JsonResponse
    {
        $request->validate([
            'signature_base64' => 'nullable|string',
            'signature_file' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
            'strokes_data' => 'nullable',
        ]);

        $employee = $request->user();
        $binaryImage = null;

        if ($request->hasFile('signature_file')) {
            $file = $request->file('signature_file');
            $binaryImage = file_get_contents($file->getRealPath());
        } elseif ($request->filled('signature_base64')) {
            $base64 = $request->signature_base64;
            // Bersihkan data URI scheme jika ada (data:image/png;base64,...)
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
            }
            $binaryImage = base64_decode($base64);
        }

        if (empty($binaryImage)) {
            return response()->json([
                'success' => false,
                'message' => 'Goresan tanda tangan tidak boleh kosong.',
                'data' => null,
            ], 422);
        }

        // Simpan gambar ke public storage disk
        $filename = 'signatures/sig_' . $employee->id . '_' . time() . '_' . Str::random(8) . '.png';
        Storage::disk('public')->put($filename, $binaryImage);

        // Parse strokes data jika berbentuk JSON string
        $strokes = $request->strokes_data;
        if (is_string($strokes)) {
            $decoded = json_decode($strokes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $strokes = $decoded;
            }
        }

        $signature = EmployeeSignature::create([
            'employee_id' => $employee->id,
            'signature_path' => $filename,
            'strokes_data' => is_array($strokes) ? json_encode($strokes) : (is_string($strokes) ? $strokes : null),
            'device_info' => $request->header('User-Agent') ?? $request->input('device_info'),
            'ip_address' => $request->ip(),
        ]);

        ActivityLogService::log(
            $employee,
            'update_signature',
            "{$employee->name} memperbarui tanda tangan digital",
            'Profil Karyawan',
            $signature
        );

        return response()->json([
            'success' => true,
            'message' => 'Tanda tangan digital berhasil disimpan',
            'data' => [
                'id' => $signature->id,
                'signature_url' => $signature->signature_url,
                'strokes_data' => $signature->strokes_data,
                'created_at' => $signature->created_at?->toISOString(),
            ],
        ]);
    }
}

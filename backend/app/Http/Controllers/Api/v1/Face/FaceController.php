<?php

namespace App\Http\Controllers\Api\v1\Face;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\RegisterFaceRequest;
use App\Http\Requests\Face\VerifyFaceRequest;
use App\Http\Resources\Face\FaceResource;
use App\Models\EmployeeFace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    /**
     * Register wajah karyawan.
     *
     * @note Verifikasi wajah asli (ML Kit) dijalankan di sisi client Android.
     *       Backend hanya menyimpan template (tersimpan apa adanya, bukan plaintext
     *       yang curatable) beserta metadata, lalu menyediakan endpoint validasi.
     */
    public function register(RegisterFaceRequest $request): JsonResponse
    {
        $employee = $request->user();

        $face = EmployeeFace::create([
            'employee_id' => $employee->id,
            'face_template' => $request->face_template,
            'face_image' => $request->face_image,
            'model_version' => $request->model_version,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wajah berhasil didaftarkan',
            'data' => new FaceResource($face),
        ], 201);
    }

    /**
     * Verifikasi token wajah.
     *
     * @note Tanpa model ML di backend, ini hanya validasi keberadaan + pencocokan
     *       string dengan template terakhir (dummy match). Integrasi ML Kit asli
     *       berjalan di sisi client Android; backend menyimpan template dan
     *       bertindak sebagai source of truth sederhana.
     */
    public function verify(VerifyFaceRequest $request): JsonResponse
    {
        $employee = $request->user();

        $face = EmployeeFace::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (!$face) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah belum terdaftar',
                'data' => null,
            ], 404);
        }

        // Dummy match: token harus sama persis dengan template terakhir.
        $matched = hash_equals((string) $face->face_template, (string) $request->face_token);

        if (!$matched) {
            return response()->json([
                'success' => false,
                'message' => 'Verifikasi wajah gagal',
                'data' => null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi wajah berhasil',
            'data' => [
                'verified' => true,
                'face_id' => $face->id,
            ],
        ]);
    }

    /**
     * Liveness detection — dummy.
     *
     * @note Liveness ML Kit sebenarnya dijalankan di client Android.
     *       Endpoint ini hanya penanda bahwa proses liveness berhasil.
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Liveness berhasil (dummy — ML Kit berjalan di client)',
            'data' => [
                'liveness_passed' => true,
            ],
        ]);
    }

    /**
     * Daftar wajah karyawan (untuk status registrasi).
     */
    public function index(Request $request): JsonResponse
    {
        $faces = $request->user()->faces()->latest('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar wajah',
            'data' => FaceResource::collection($faces)->response()->getData(true),
        ]);
    }
}

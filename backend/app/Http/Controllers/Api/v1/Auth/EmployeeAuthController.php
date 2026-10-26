<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Helpers\FaceMatchHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required_without:email|string',
            'email' => 'required_without:employee_id|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
            'device_id' => 'nullable|string',
        ]);

        $employee = Employee::where(function ($q) use ($request) {
            if ($request->filled('email')) {
                $q->where('email', $request->email);
            }
            if ($request->filled('employee_id')) {
                $q->orWhere('employee_id', $request->employee_id)
                  ->orWhereHas('assignments', fn ($sq) => $sq->where('nip', $request->employee_id));
            }
        })->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        if (!$employee->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun nonaktif.'],
            ]);
        }

        $deviceCount = \App\Models\EmployeeDevice::where('employee_id', $employee->id)->count();

        // Jika belum ada device terdaftar sama sekali dan status masih pending, anggap ini login pertama
        // dan berikan akses secara otomatis (auto-approve).
        if ($deviceCount === 0 && $employee->mobile_access_status === 'pending') {
            $employee->mobile_access_status = 'approved';
            $employee->save();
        }

        if ($employee->mobile_access_status !== 'approved') {
            throw ValidationException::withMessages([
                'email' => ['Akses aplikasi mobile Anda belum disetujui (Status: ' . $employee->mobile_access_status . '). Hubungi HR.'],
            ]);
        }

        // ============================
        // DEVICE LOCK: Validasi Device
        // ============================
        if ($request->filled('device_id')) {
            $incomingDeviceId = $request->device_id;

            // Cari device yang saat ini disetujui untuk pegawai ini
            $approvedDevice = EmployeeDevice::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->first();

            if ($approvedDevice !== null) {
                if ($approvedDevice->device_id === $incomingDeviceId) {
                    // Device sama dengan yang disetujui → perbarui waktu aktif
                    $approvedDevice->update(['last_active_at' => now()]);
                } else {
                    // Device BERBEDA dari device yang sudah disetujui untuk akun ini!
                    $existing = EmployeeDevice::where('employee_id', $employee->id)
                        ->where('device_id', $incomingDeviceId)
                        ->first();

                    if (!$existing) {
                        EmployeeDevice::create([
                            'employee_id'   => $employee->id,
                            'device_id'     => $incomingDeviceId,
                            'device_name'   => $request->device_name ?? 'Android',
                            'platform'      => 'android',
                            'status'        => 'pending',
                            'last_active_at' => now(),
                        ]);
                    } elseif ($existing->status === 'rejected') {
                        $existing->update([
                            'device_name'    => $request->device_name ?? $existing->device_name,
                            'status'         => 'pending',
                            'last_active_at' => now(),
                        ]);
                    }

                    throw ValidationException::withMessages([
                        'email' => [
                            'Perangkat baru terdeteksi. Akun Anda terikat pada perangkat lain. Akses dari perangkat ini sedang menunggu persetujuan Admin. Silakan hubungi HR.'
                        ],
                    ]);
                }
            } else {
                // Belum ada device berstatus 'approved'
                // Cek apakah pegawai ini SUDAH PERNAH mendaftarkan/menggunakan device di sistem sebelumnya
                $hasAnyDeviceHistory = EmployeeDevice::where('employee_id', $employee->id)->exists();

                if (!$hasAnyDeviceHistory) {
                    // Pertama kali login di sistem (belum ada history device sama sekali) → auto-approve device pertama
                    EmployeeDevice::create([
                        'employee_id'   => $employee->id,
                        'device_id'     => $incomingDeviceId,
                        'device_name'   => $request->device_name ?? 'Android',
                        'platform'      => 'android',
                        'status'        => 'approved',
                        'approved_at'   => now(),
                        'last_active_at' => now(),
                    ]);
                } else {
                    // Sistem SUDAH MENDETEKSI bahwa akun ini sebelumnya sudah memakai device (ada history),
                    // tetapi saat ini device ini belum/tidak berstatus approved (pending / dicabut / rejected oleh Admin).
                    $existing = EmployeeDevice::where('employee_id', $employee->id)
                        ->where('device_id', $incomingDeviceId)
                        ->first();

                    if (!$existing) {
                        EmployeeDevice::create([
                            'employee_id'   => $employee->id,
                            'device_id'     => $incomingDeviceId,
                            'device_name'   => $request->device_name ?? 'Android',
                            'platform'      => 'android',
                            'status'        => 'pending',
                            'last_active_at' => now(),
                        ]);
                    } elseif ($existing->status === 'rejected') {
                        $existing->update([
                            'device_name'    => $request->device_name ?? $existing->device_name,
                            'status'         => 'pending',
                            'last_active_at' => now(),
                        ]);
                    }

                    throw ValidationException::withMessages([
                        'email' => [
                            'Perangkat ini belum disetujui. Silakan tunggu verifikasi/persetujuan dari Admin.'
                        ],
                    ]);
                }
            }
        }

        // Set expiration 10 tahun agar token awet tapi tidak melampaui limit TIMESTAMP (Y2K38)
        $token = $employee->createToken('employee-auth', ['employee'], now()->addYears(10))->plainTextToken;

        $primaryAssignment = $employee->primaryAssignment ?? $employee->assignments()->where('is_primary', true)->first() ?? $employee->assignments()->latest('id')->first();
        if ($primaryAssignment) {
            $primaryAssignment->load(['company', 'position', 'branch']);
        }
        $legalCompany = $primaryAssignment?->company ?? $employee->company;
        $position = $primaryAssignment?->position ?? $employee->position;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'company' => $legalCompany?->name,
                    'position' => $position?->name,
                    'branch_id' => $primaryAssignment?->branch_id ?? $employee->branch_id,
                    'branch' => $primaryAssignment?->branch?->name ?? $employee->branch?->name,
                    'can_clock_in_anywhere' => (bool)$employee->can_clock_in_anywhere,
                    'has_subordinates' => (bool)$employee->hasSubordinates(),
                    'subordinates_count' => (int)$employee->subordinatesCount(),
                    'photo_url' => $employee->photo_url,
                ],
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $employee = $request->user();

        if ($request->has('name')) {
            $employee->name = $request->name;
        }

        if ($request->has('phone')) {
            $employee->phone = $request->phone;
        }
        
        if ($request->has('address')) {
            $employee->address = $request->address;
        }

        if ($request->has('birth_date')) {
            $employee->birth_date = $request->birth_date;
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $ext = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
            $filename = \Illuminate\Support\Str::random(20) . '.' . $ext;
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $path = $file->storeAs('employees', $filename, 'public');
            $employee->photo = $path;
        }

        $employee->save();
        $employee->load([
            'primaryAssignment.company', 
            'primaryAssignment.branch', 
            'primaryAssignment.department', 
            'primaryAssignment.position', 
            'documents', 
            'educations', 
            'trainings'
        ]);

        \App\Services\ActivityLogService::log(
            $employee, 
            'update_profile', 
            "{$employee->name} memperbarui data profil", 
            'Profil Karyawan', 
            $employee
        );

        $employeeData = array_merge($employee->toArray(), [
            'photo_url' => $employee->photo_url,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => $employeeData
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
            'data' => null,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $employee = $request->user();

        if (!Hash::check($request->current_password, $employee->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini salah.'],
            ]);
        }

        $employee->password = Hash::make($request->new_password);
        $employee->save();

        // revoke token lain
        $employee->tokens()->where('id', '!=', $employee->currentAccessToken()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah',
            'data' => null,
        ]);
    }

    public function devices(Request $request): JsonResponse
    {
        $devices = $request->user()->devices;

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $devices,
        ]);
    }

    public function revokeDevice(Request $request, $deviceId): JsonResponse
    {
        $device = EmployeeDevice::where('employee_id', $request->user()->id)
            ->where('id', $deviceId)->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device tidak ditemukan', 'data' => null], 404);
        }

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device dicabut',
            'data' => null,
        ]);
    }

    /**
     * Registrasi wajah karyawan (simpan foto referensi).
     * Request: { face_photo: "base64..." }
     */
    public function registerFace(Request $request): JsonResponse
    {
        $request->validate([
            'face_photo' => 'required|string',
        ]);

        /** @var Employee $employee */
        $employee = $request->user();

        // Decode base64
        $base64 = $request->face_photo;
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }
        $imageData = base64_decode($base64, strict: true);
        if (!$imageData) {
            return response()->json(['success' => false, 'message' => 'Data foto tidak valid.', 'data' => null], 422);
        }

        // Validasi bahwa data adalah gambar yang valid
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            return response()->json(['success' => false, 'message' => 'Format gambar tidak didukung.', 'data' => null], 422);
        }

        // Tentukan ekstensi
        $ext = match($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        // Hapus foto lama jika ada
        if ($employee->face_photo) {
            Storage::disk('public')->delete($employee->face_photo);
        }

        // Simpan foto baru
        $path = 'faces/' . $employee->id . '_' . time() . '.' . $ext;
        Storage::disk('public')->put($path, $imageData);

        $employee->update([
            'face_photo'          => $path,
            'face_registered_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wajah berhasil didaftarkan.',
            'data'    => [
                'face_photo_url'     => Storage::disk('public')->url($path),
                'face_registered_at' => $employee->face_registered_at,
            ],
        ]);
    }

    /**
     * Cek apakah employee sudah register wajah.
     */
    public function faceStatus(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $hasFace = !empty($employee->face_photo) &&
                   Storage::disk('public')->exists($employee->face_photo);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'has_face'           => $hasFace,
                'face_registered_at' => $employee->face_registered_at,
                'face_photo_url'     => $hasFace
                    ? Storage::disk('public')->url($employee->face_photo)
                    : null,
            ],
        ]);
    }
}

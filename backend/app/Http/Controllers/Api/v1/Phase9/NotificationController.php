<?php

namespace App\Http\Controllers\Api\v1\Phase9;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\BroadcastNotificationRequest;
use App\Http\Requests\Notification\DeviceTokenRequest;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\DeviceToken;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 9: NOTIFIKASI IN-APP + FCM DEVICE TOKEN
 * Endpoint employee (/my/notifications, /my/device-tokens) & admin (/notifications).
 */
class NotificationController extends Controller
{
    // ===================== EMPLOYEE =====================

    /** GET /my/notifications — list notifikasi milik saya */
    public function myIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Notification::query()
            ->where('employee_id', $user->id)
            ->latest();

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        $limit = (int) $request->get('limit', 15);
        $limit = max(1, min($limit, 100));

        $paginator = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'data' => NotificationResource::collection($paginator),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ]
        ]);
    }

    /** GET /my/notifications/unread-count — badge dot */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = Notification::query()
            ->where('employee_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Sukses',
            'data' => ['count' => $count],
        ]);
    }

    /** PUT /my/notifications/{notification}/read — tandai dibaca */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        $user = $request->user();

        abort_if($notification->employee_id !== $user->id, 403, 'Akses ditolak.');

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai dibaca.',
            'data' => new NotificationResource($notification->refresh()),
        ]);
    }

    /** PUT /my/notifications/read-all — tandai semua milik saya dibaca */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $updated = Notification::query()
            ->where('employee_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai dibaca.',
            'data' => ['updated' => $updated],
        ]);
    }

    /** DELETE /my/notifications/{notification} — hapus milik saya */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $user = $request->user();

        abort_if($notification->employee_id !== $user->id, 403, 'Akses ditolak.');

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi dihapus.',
            'data' => null,
        ]);
    }

    /** POST /my/device-tokens — daftarkan FCM token */
    public function registerDeviceToken(DeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $token = DeviceToken::updateOrCreate(
            ['employee_id' => $user->id, 'token' => $data['token']],
            [
                'platform' => $data['platform'],
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token terdaftar.',
            'data' => [
                'id' => $token->id,
                'employee_id' => $token->employee_id,
                'platform' => $token->platform,
                'token' => $token->token,
                'is_active' => $token->is_active,
                'created_at' => $token->created_at?->toISOString(),
                'updated_at' => $token->updated_at?->toISOString(),
            ],
        ]);
    }

    // ===================== ADMIN =====================

    /** POST /notifications/send — kirim manual ke satu employee */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $notification = Notification::create([
            'employee_id' => $data['employee_id'],
            'type' => $data['type'] ?? 'manual',
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'data' => $data['data'] ?? null,
            'is_read' => false,
            'read_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi terkirim.',
            'data' => new NotificationResource($notification->load('employee')),
        ], 201);
    }

    /** GET /notifications — list semua, filter */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Notification::query()
            ->with('employee:id,employee_id,name')
            ->latest();

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $limit = (int) $request->get('limit', 15);
        $limit = max(1, min($limit, 100));

        return NotificationResource::collection(
            $query->paginate($limit)
        );
    }

    /** POST /notifications/broadcast — kirim ke semua / beberapa employee (opsional) */
    public function broadcast(BroadcastNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $employeeIds = $data['employee_ids']
            ?? Employee::query()->where('is_active', true)->pluck('id')->all();

        if (empty($employeeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada employee penerima.',
                'data' => ['sent' => 0],
            ], 422);
        }

        $now = now();
        $rows = collect($employeeIds)->map(fn ($id) => [
            'employee_id' => $id,
            'type' => $data['type'] ?? 'broadcast',
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'data' => isset($data['data']) ? json_encode($data['data']) : null,
            'is_read' => false,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        $sent = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            $sent += DB::table('notifications')->insertOrIgnore($chunk);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi broadcast terkirim.',
            'data' => ['sent' => $sent],
        ], 201);
    }
}

<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * Catat log aktivitas dan buat notifikasi untuk pelaku (actor).
     *
     * @param Model $actor Pengguna yang melakukan aksi (misal Employee)
     * @param string $action Nama aksi singkat (misal 'create_leave')
     * @param string $description Deskripsi aksi (misal 'Si fulan mengajukan cuti pada tanggal...')
     * @param string $module Nama modul atau menu (misal 'Pengajuan Cuti')
     * @param Model|null $subject Entitas yang dipengaruhi (opsional)
     */
    public static function log(Model $actor, string $action, string $description, string $module, Model $subject = null)
    {
        // 1. Simpan ke activity_logs
        ActivityLog::create([
            'actor_type' => get_class($actor),
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'properties' => [
                'description' => $description,
                'module' => $module,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // 2. Buat notifikasi untuk employee tersebut (In-App Notification)
        // Kita asumsikan $actor adalah Employee
        if (get_class($actor) === \App\Models\Employee::class) {
            Notification::create([
                'employee_id' => $actor->id,
                'title' => $module,
                'message' => $description,
                'type' => $action,
                'is_read' => false,
            ]);
        }
    }
}

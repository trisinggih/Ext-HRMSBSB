<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    // For Admin: List all announcements
    public function index(Request $request)
    {
        $announcements = Announcement::orderByDesc('created_at')->paginate($request->integer('per_page', 20));
        
        // Append full URL for attachments
        $announcements->getCollection()->transform(function ($announcement) {
            if ($announcement->attachment_path) {
                $announcement->attachment_url = url(Storage::url($announcement->attachment_path));
            } else {
                $announcement->attachment_url = null;
            }
            return $announcement;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengumuman',
            'data' => $announcements
        ]);
    }

    // For Admin: Create an announcement
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $data = $request->only(['title', 'body']);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $ext = $file->getClientOriginalExtension();
            $data['attachment_type'] = in_array(strtolower($ext), ['pdf']) ? 'pdf' : 'image';
            $data['attachment_path'] = $file->store('announcements', 'public');
        }

        $announcement = Announcement::create($data);

        // Broadcast push notification to all employees via FCMService
        $tokens = \App\Models\Employee::whereNotNull('fcm_token')
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->toArray();
        
        if (!empty($tokens)) {
            $fcm = new \App\Services\FCMService();
            $fcm->sendToMultipleDevices(
                $tokens,
                "📢 Pengumuman Baru: " . $announcement->title,
                "Buka aplikasi untuk melihat pengumuman terbaru.",
                [
                    'type' => 'announcement',
                    'id' => $announcement->id
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil dibuat dan disiarkan.',
            'data' => $announcement
        ]);
    }

    // For Admin: Delete an announcement
    public function destroy(Announcement $announcement)
    {
        if ($announcement->attachment_path) {
            Storage::disk('public')->delete($announcement->attachment_path);
        }
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil dihapus.'
        ]);
    }

    // For Mobile: Fetch announcements
    public function mobileIndex(Request $request)
    {
        $employee = $request->user();
        $announcements = Announcement::orderByDesc('created_at')->paginate($request->integer('per_page', 20));
        
        // Append full URL for attachments
        $announcements->getCollection()->transform(function ($announcement) use ($employee) {
            if ($announcement->attachment_path) {
                $announcement->attachment_url = url(Storage::url($announcement->attachment_path));
            } else {
                $announcement->attachment_url = null;
            }
            
            // Check if read by this employee
            if ($employee) {
                $announcement->is_read = \Illuminate\Support\Facades\DB::table('announcement_reads')
                    ->where('announcement_id', $announcement->id)
                    ->where('employee_id', $employee->id)
                    ->exists();
            } else {
                $announcement->is_read = false;
            }
            
            return $announcement;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengumuman',
            'data' => $announcements
        ]);
    }

    // For Mobile: Mark announcement as read
    public function markAsRead(Request $request, $id)
    {
        $employee = $request->user();
        if ($employee) {
            \Illuminate\Support\Facades\DB::table('announcement_reads')->updateOrInsert(
                ['announcement_id' => $id, 'employee_id' => $employee->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        return response()->json(['success' => true]);
    }
}

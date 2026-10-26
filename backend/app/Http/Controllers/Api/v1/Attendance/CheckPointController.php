<?php

namespace App\Http\Controllers\Api\v1\Attendance;

use App\Http\Controllers\Controller;
use App\Models\CheckPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckPointController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = CheckPoint::with([
            'employee:id,name,employee_id',
            'employee.company',
            'employee.department',
            'employee.position',
        ]);

        // If authenticated user is an employee (not admin access), filter by own employee id
        if ($user && $user instanceof \App\Models\Employee && !$user->canAccessWeb()) {
            $query->where('employee_id', $user->id);
        } else {
            // Admin query filters
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->filled('client_name')) {
                $query->where('client_name', 'like', '%' . $request->client_name . '%');
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('client_name', 'like', "%{$search}%")
                      ->orWhere('purpose', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('name', 'like', "%{$search}%")
                             ->orWhere('employee_id', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }
        }

        $query->orderBy('created_at', 'desc');

        // Check if pagination requested or admin request
        if ($request->has('per_page') || $request->has('page')) {
            $checkpoints = $query->paginate($request->integer('per_page', 15));
            return response()->json([
                'success' => true,
                'message' => 'Daftar check point berhasil diambil',
                'data' => $checkpoints
            ]);
        }

        $checkpoints = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar check point berhasil diambil',
            'data' => $checkpoints
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:10240'
        ]);

        if (!$request->hasFile('photo') && !$request->hasFile('photos')) {
            return response()->json(['success' => false, 'message' => 'Mohon lampirkan foto kunjungan'], 422);
        }

        $employeeId = $request->user()->id;
        $photoPath = null;
        $photosList = [];

        // Handle single photo if provided
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('checkpoints', $filename, 'public');
            $photosList[] = $photoPath;
        }

        // Handle multiple photos if provided
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('checkpoints', $filename, 'public');
                $photosList[] = $path;
                
                // If photoPath is still null, set it to the first multiple photo for backward compatibility
                if (!$photoPath) {
                    $photoPath = $path;
                }
            }
        }

        $checkpoint = CheckPoint::create([
            'employee_id' => $employeeId,
            'client_name' => $request->client_name,
            'purpose' => $request->purpose,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
            'photo_path' => $photoPath,
            'photos' => $photosList,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check point berhasil disimpan',
            'data' => $checkpoint
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\v1\Attendance;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLocation;
use App\Models\LocationTrackingSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CanvassingController extends Controller
{
    /**
     * Dapatkan titik lokasi terakhir masing-masing sales hari ini.
     */
    public function liveLocations(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        
        $sessions = LocationTrackingSession::with(['employee:id,name,photo'])
            ->where('date', $today)
            ->get();

        $liveData = [];
        foreach ($sessions as $session) {
            $latestLocation = EmployeeLocation::where('session_id', $session->id)
                ->orderByDesc('timestamp')
                ->first();
                
            if ($latestLocation) {
                $liveData[] = [
                    'employee' => $session->employee,
                    'location' => [
                        'latitude' => $latestLocation->latitude,
                        'longitude' => $latestLocation->longitude,
                        'timestamp' => $latestLocation->timestamp,
                        'accuracy' => $latestLocation->accuracy,
                    ]
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $liveData,
        ]);
    }

    /**
     * Dapatkan rute/poly-line perjalanan seorang sales.
     */
    public function locationHistory(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'nullable|date',
        ]);

        $date = $request->date ?? now()->toDateString();

        $session = LocationTrackingSession::where('employee_id', $request->employee_id)
            ->where('date', $date)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $locations = EmployeeLocation::where('session_id', $session->id)
            ->orderBy('timestamp', 'asc')
            ->get(['latitude', 'longitude', 'timestamp', 'accuracy']);

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }
}

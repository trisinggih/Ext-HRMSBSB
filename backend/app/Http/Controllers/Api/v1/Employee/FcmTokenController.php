<?php

namespace App\Http\Controllers\Api\v1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $employee = $request->user();
        if ($employee) {
            $employee->update(['fcm_token' => $request->fcm_token]);
            return response()->json([
                'success' => true,
                'message' => 'FCM Token updated successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForbidStaffPayrollMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof Employee && $user->web_role === 'staff_hr') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Fitur Payroll dan hitungan gaji tidak dapat diakses oleh level Staff.',
                'data'    => null,
            ], 403);
        }

        return $next($request);
    }
}

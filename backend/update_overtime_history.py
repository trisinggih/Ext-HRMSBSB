import re

with open('routes/api.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Add Route::get('/history', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'history']);
if "Route::get('/history'" not in content:
    content = content.replace("Route::post('/request'", "Route::get('/history', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'history']);\n                Route::post('/request'")
    with open('routes/api.php', 'w', encoding='utf-8') as f:
        f.write(content)


with open('app/Http/Controllers/Api/v1/Mobile/MobileOvertimeController.php', 'r', encoding='utf-8') as f:
    content = f.read()

history_method = """
    public function history(Request $request): JsonResponse
    {
        $employeeId = $request->user()->employee->id;
        $history = OvertimeRequest::where('employee_id', $employeeId)->orderBy('created_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil riwayat lembur',
            'data' => $history
        ]);
    }
"""

if "public function history" not in content:
    content = content.replace("class MobileOvertimeController extends Controller\n{", "class MobileOvertimeController extends Controller\n{" + history_method)
    with open('app/Http/Controllers/Api/v1/Mobile/MobileOvertimeController.php', 'w', encoding='utf-8') as f:
        f.write(content)

print("Backend API routes and methods updated.")

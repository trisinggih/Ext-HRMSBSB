<?php

namespace App\Http\Controllers\Api\v1\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Attendance;
use Carbon\Carbon;

class KioskController extends Controller
{
    public function verifyPin(Request $request)
    {
        $request->validate(["pin" => "required|string"]);
        $company = Company::where("kiosk_pin", $request->pin)->where("is_active", true)->first();

        if (!$company) {
            return response()->json(["message" => "PIN Kiosk tidak valid atau tidak ditemukan."], 401);
        }

        return response()->json(["message" => "PIN Kiosk valid", "company_id" => $company->id, "company_name" => $company->name]);
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            "company_id" => "required|integer",
            "nip" => "required|string",
            "photo" => "required|string"
        ]);

        $employee = Employee::where("nip", $request->nip)
            ->whereHas("assignments", function ($q) use ($request) {
                $q->where("company_id", $request->company_id);
            })->first();

        if (!$employee) {
            return response()->json(["message" => "NIP tidak ditemukan di perusahaan ini."], 404);
        }

        $today = Carbon::today();
        
        $attendance = Attendance::where("employee_id", $employee->id)
            ->whereDate("date", $today)
            ->first();

        if ($attendance && $attendance->clock_in) {
            return response()->json(["message" => "Anda sudah melakukan absen masuk hari ini."], 400);
        }

        if (!$attendance) {
            $attendance = new Attendance();
            $attendance->employee_id = $employee->id;
            $attendance->date = $today;
        }

        $now = Carbon::now();
        $attendance->clock_in = $now->format("H:i:s");
        $attendance->status = "present";
        
        // Handle photo upload (base64)
        if (preg_match('/^data:image\/(\w+);base64,/', $request->photo, $type)) {
            $data = substr($request->photo, strpos($request->photo, ",") + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);
            $filename = "kiosk_in_" . $employee->id . "_" . time() . "." . $type;
            \Storage::disk("public")->put("attendance/" . $filename, $data);
            $attendance->clock_in_photo = "attendance/" . $filename;
        }
        
        $attendance->save();

        return response()->json([
            "message" => "Absen masuk berhasil.",
            "data" => [
                "employee_name" => $employee->name,
                "time" => $attendance->clock_in
            ]
        ]);
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            "company_id" => "required|integer",
            "nip" => "required|string",
            "photo" => "required|string"
        ]);

        $employee = Employee::where("nip", $request->nip)
            ->whereHas("assignments", function ($q) use ($request) {
                $q->where("company_id", $request->company_id);
            })->first();

        if (!$employee) {
            return response()->json(["message" => "NIP tidak ditemukan di perusahaan ini."], 404);
        }

        $today = Carbon::today();
        
        $attendance = Attendance::where("employee_id", $employee->id)
            ->whereDate("date", $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return response()->json(["message" => "Anda belum melakukan absen masuk hari ini."], 400);
        }

        if ($attendance->clock_out) {
            return response()->json(["message" => "Anda sudah melakukan absen pulang hari ini."], 400);
        }

        $now = Carbon::now();
        $attendance->clock_out = $now->format("H:i:s");
        
        // Handle photo upload (base64)
        if (preg_match('/^data:image\/(\w+);base64,/', $request->photo, $type)) {
            $data = substr($request->photo, strpos($request->photo, ",") + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);
            $filename = "kiosk_out_" . $employee->id . "_" . time() . "." . $type;
            \Storage::disk("public")->put("attendance/" . $filename, $data);
            $attendance->clock_out_photo = "attendance/" . $filename;
        }
        
        $attendance->save();

        return response()->json([
            "message" => "Absen pulang berhasil.",
            "data" => [
                "employee_name" => $employee->name,
                "time" => $attendance->clock_out
            ]
        ]);
    }
}

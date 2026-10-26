<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function getProvinces()
    {
        $provinces = \App\Models\Province::orderBy('name', 'asc')->get();
        return response()->json(['success' => true, 'data' => $provinces]);
    }

    public function getCities($province_code)
    {
        $cities = \App\Models\City::where('province_code', $province_code)->orderBy('name', 'asc')->get();
        return response()->json(['success' => true, 'data' => $cities]);
    }
}

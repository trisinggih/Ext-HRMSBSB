<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetType::with('company:id,name')
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('code', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->latest();

        if ($request->has('per_page') || $request->has('page')) {
            $perPage = (int) $request->get('per_page', 10);
            return response()->json($query->paginate($perPage));
        }

        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'nullable|exists:companies,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $type = AssetType::create($validator->validated());
        return response()->json(['status' => 'success', 'message' => 'Tipe aset dibuat', 'data' => $type]);
    }

    public function show($id)
    {
        $type = AssetType::with('company:id,name')->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $type]);
    }

    public function update(Request $request, $id)
    {
        $type = AssetType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company_id' => 'nullable|exists:companies,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $type->update($validator->validated());
        return response()->json(['status' => 'success', 'message' => 'Tipe aset diperbarui', 'data' => $type]);
    }

    public function destroy($id)
    {
        $type = AssetType::findOrFail($id);
        $type->delete();
        return response()->json(['status' => 'success', 'message' => 'Tipe aset dihapus']);
    }
}

<?php

namespace App\Http\Controllers\Api\v1\Master;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class OrganizationChartController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Company::with(['branches' => function ($branch) {
            $branch->with(['departments' => function ($dept) {
                $dept->with(['divisions' => function ($div) {
                    $div->with(['positions' => function ($pos) {
                        $pos->withoutExecutives()->with(['employees' => fn ($e) => $e->withoutExecutives()->where('is_active', true)->orderBy('name')]);
                    }])->orderBy('name');
                }])->orderBy('name');
            }])->orderBy('name');
        }])->orderBy('name')->get();

        $data = $companies->map(function (Company $company) {
            return [
                'id' => $company->id,
                'type' => 'company',
                'name' => $company->name,
                'children' => $company->branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'type' => 'branch',
                        'name' => $branch->name,
                        'children' => $branch->departments->map(function ($dept) {
                            return [
                                'id' => $dept->id,
                                'type' => 'department',
                                'name' => $dept->name,
                                'children' => $dept->divisions->map(function ($div) {
                                    return [
                                        'id' => $div->id,
                                        'type' => 'division',
                                        'name' => $div->name,
                                        'children' => $div->positions->map(fn ($pos) => [
                                            'id' => $pos->id,
                                            'type' => 'position',
                                            'name' => $pos->name,
                                            'children' => $pos->employees->map(fn ($e) => [
                                                'id' => $e->id,
                                                'type' => 'employee',
                                                'name' => $e->name,
                                                'employee_id' => $e->employee_id,
                                                'photo' => $e->photo ? asset('storage/' . $e->photo) : null,
                                            ])->values(),
                                        ])->values(),
                                    ];
                                })->values(),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Organization chart',
            'data' => $data,
        ]);
    }
}

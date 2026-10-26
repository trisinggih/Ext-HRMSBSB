<?php

use App\Http\Controllers\Api\v1\Auth\AdminAuthController;
use App\Http\Controllers\Api\v1\Auth\EmployeeAuthController;
use App\Http\Controllers\Api\v1\Auth\EmployeeWebAuthController;
use App\Http\Controllers\Api\v1\Attendance\AttendanceController;
use App\Http\Controllers\Api\v1\Face\FaceController;
use App\Http\Controllers\Api\v1\Shift\ShiftController;
use App\Http\Controllers\Api\v1\Master\CompanyController;
use App\Http\Controllers\Api\v1\Master\BranchController;
use App\Http\Controllers\Api\v1\Master\DepartmentController;
use App\Http\Controllers\Api\v1\Master\DivisionController;
use App\Http\Controllers\Api\v1\Master\PositionController;
use App\Http\Controllers\Api\v1\Master\EmployeeDocumentController;
use App\Http\Controllers\Api\v1\Master\EmployeeEducationController;
use App\Http\Controllers\Api\v1\Master\EmployeeTrainingController;
use App\Http\Controllers\Api\v1\Master\EmployeeMutationController;
use App\Http\Controllers\Api\v1\Master\EmployeeController;
use App\Http\Controllers\Api\v1\Master\OrganizationChartController;
use App\Http\Controllers\Api\v1\Payroll\PayrollComponentController;
use App\Http\Controllers\Api\v1\Payroll\SalaryStructureController;
use App\Http\Controllers\Api\v1\Payroll\PtkpSettingController;
use App\Http\Controllers\Api\v1\Payroll\PayrollController;
use App\Http\Controllers\Api\v1\Phase9\NotificationController;
use App\Http\Controllers\Api\v1\Admin\EmployeeApprovalSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Ext-HRMS /api/v1
|--------------------------------------------------------------------------
| Web Admin ↔ Mobile Employee memakai API yang sama.
*/

Route::prefix('v1')->group(function () {

    Route::get('/regions/provinces', [\App\Http\Controllers\RegionController::class, 'getProvinces']);
    Route::get('/regions/cities/{province_code}', [\App\Http\Controllers\RegionController::class, 'getCities']);

    // ===== AUTH ADMIN (tabel admins) =====
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AdminAuthController::class, 'login'])->name('api.admin.login');
    });

    // ===== AUTH EMPLOYEE (tabel employees) — mobile =====
    Route::prefix('employee-auth')->group(function () {
        Route::post('/login', [EmployeeAuthController::class, 'login'])->name('api.employee.login');
    });

    // ===== AUTH EMPLOYEE — WEB (login via browser, bukan mobile) =====
    Route::prefix('web/auth')->group(function () {
        Route::post('/login', [EmployeeWebAuthController::class, 'login'])->name('api.employee.web.login');
    });

    // ===================== RMS — PUBLIC (tanpa auth) =====================
    Route::prefix('recruitment')->group(function () {
        Route::get('/jobs', [\App\Http\Controllers\Api\v1\Recruitment\PublicRecruitmentController::class, 'jobs'])->name('api.recruitment.public.jobs');
        Route::get('/jobs/{job}', [\App\Http\Controllers\Api\v1\Recruitment\PublicRecruitmentController::class, 'jobDetail'])->name('api.recruitment.public.jobs.show');
        Route::post('/apply', [\App\Http\Controllers\Api\v1\Recruitment\PublicRecruitmentController::class, 'apply'])->name('api.recruitment.public.apply');
        Route::post('/documents', [\App\Http\Controllers\Api\v1\Recruitment\PublicRecruitmentController::class, 'uploadDocuments'])->name('api.recruitment.public.documents');
        Route::get('/tests/{test}', [\App\Http\Controllers\Api\v1\Recruitment\PublicRecruitmentController::class, 'testDetail'])->name('api.recruitment.public.tests.show');
        Route::post('/tests/{test}/submit', [\App\Http\Controllers\Api\v1\Recruitment\PublicRecruitmentController::class, 'submitTest'])->name('api.recruitment.public.tests.submit');
        Route::get('/status/{candidate}', [\App\Http\Controllers\Api\v1\Recruitment\PublicRecruitmentController::class, 'status'])->name('api.recruitment.public.status');
    });

    // ===================== CMS — PUBLIC (tanpa auth) =====================
    Route::prefix('cms')->group(function () {
        Route::get('/site', [\App\Http\Controllers\Api\v1\Cms\PublicCmsController::class, 'site'])->name('api.cms.public.site');
        Route::get('/pages/{slug}', [\App\Http\Controllers\Api\v1\Cms\PublicCmsController::class, 'page'])->where('slug', '[a-zA-Z0-9-\\_]+')->name('api.cms.public.pages.show');
        Route::get('/sections/{key}', [\App\Http\Controllers\Api\v1\Cms\PublicCmsController::class, 'section'])->name('api.cms.public.sections.show');
        // Endpoint publik untuk landing page React
        Route::get('/public/settings', [\App\Http\Controllers\Api\v1\Public\CmsController::class, 'getSettings'])->name('api.cms.public.settings');
        Route::get('/public/products', [\App\Http\Controllers\Api\v1\Public\CmsController::class, 'getProducts'])->name('api.cms.public.products');
    });

    // ===================== COMPANY — PUBLIC (tanpa auth) =====================
    $companyLogoHandler = function () {
        $group = \App\Models\CompanyGroup::first();
        if ($group) {
            $data = $group->toArray();
            $logoPath = $group->logo;
            if ($logoPath && !\Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
                $logoPath = null;
            }
            $data['logo'] = $logoPath ? url(\Illuminate\Support\Facades\Storage::url($logoPath)) : null;
            $data['favicon'] = $group->favicon ? url(\Illuminate\Support\Facades\Storage::url($group->favicon)) : null;
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }
        
        // Fallback to old behavior if no group exists
        $company = \App\Models\Company::first();
        if ($company) {
            $data = $company->toArray();
            $logoPath = $company->logo;
            if ($logoPath && !\Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
                $logoPath = null;
            }
            $data['logo'] = $logoPath ? url(\Illuminate\Support\Facades\Storage::url($logoPath)) : null;
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Company not found'
        ], 404);
    };

    Route::get('/company/logo', $companyLogoHandler)->name('api.company.public.logo');
    Route::get('/company-logo', $companyLogoHandler)->name('api.company.public.logo-alias');

    // Health check (Public ping)
    Route::get('/ping', function () {
        return response()->json(['success' => true, 'message' => 'pong', 'data' => null]);
    })->name('api.public.ping');

    // ===================== EXPORT PDF (Direct Access / Manual Token) =====================
    Route::get('/reports/export-pdf', [\App\Http\Controllers\Api\v1\ReportController::class, 'exportPdf'])->name('api.reports.export-pdf');
    Route::get('/asset-requests/{id}/pdf', [\App\Http\Controllers\AssetRequestController::class, 'exportPdf'])->name('api.asset-requests.pdf');
    Route::get('/asset-repairs/{id}/pdf', [\App\Http\Controllers\AssetRepairController::class, 'exportPdf'])->name('api.asset-repairs.pdf');
    Route::get('/employee-mutations/{id}/pdf', [\App\Http\Controllers\Api\v1\Master\EmployeeMutationController::class, 'exportPdf'])->name('api.employee-mutations.pdf.direct');

    // ===== PROTECTED (token) =====
    Route::middleware('auth:sanctum')->group(function () {

        // Admin protected
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AdminAuthController::class, 'logout']);
            Route::get('/profile', [AdminAuthController::class, 'profile'])->name('api.admin.profile');
        });

        // Employee Web protected
        Route::prefix('web/auth')->group(function () {
            Route::get('/me', [EmployeeWebAuthController::class, 'me'])->name('api.employee.web.me');
            Route::post('/logout', [EmployeeWebAuthController::class, 'logout'])->name('api.employee.web.logout');
        });

        // Employee protected
        Route::prefix('employee-auth')->group(function () {
            Route::post('/logout', [EmployeeAuthController::class, 'logout']);
            Route::post('/change-password', [EmployeeAuthController::class, 'changePassword']);
            Route::get('/devices', [EmployeeAuthController::class, 'devices']);
            Route::delete('/devices/{deviceId}', [EmployeeAuthController::class, 'revokeDevice']);
            // Face Registration
            Route::post('/register-face', [EmployeeAuthController::class, 'registerFace'])->name('api.employee.register-face');
            Route::get('/face-status', [EmployeeAuthController::class, 'faceStatus'])->name('api.employee.face-status');
            Route::post('/fcm-token', [\App\Http\Controllers\Api\v1\Employee\FcmTokenController::class, 'store'])->name('api.employee.fcm-token');
        });

        Route::get('/employee/profile', function (\Illuminate\Http\Request $request) {
            $e = $request->user();
            $assignment = $e->primaryAssignment ?? $e->assignments()->latest('id')->first();
            if ($assignment) {
                $assignment->load(['company', 'branch', 'department', 'division', 'position']);
            }
            $e->load(['documents', 'educations', 'trainings']);
            
            $data = [
                'id' => $e->id,
                'employee_id' => $e->employee_id,
                'name' => $e->name,
                'email' => $e->email,
                'phone' => $e->phone,
                'address' => $e->address,
                'birth_date' => $e->birth_date ? \Carbon\Carbon::parse($e->birth_date)->format('Y-m-d') : null,
                'company' => $assignment?->company?->name,
                'position' => $assignment?->position?->name,
                'division' => $assignment?->division?->name,
                'department' => $assignment?->department?->name,
                'branch_id' => $assignment?->branch_id,
                'branch' => $assignment?->branch?->name,
                'can_clock_in_anywhere' => (bool)$e->can_clock_in_anywhere,
                'has_subordinates' => (bool)$e->hasSubordinates(),
                'subordinates_count' => (int)$e->subordinatesCount(),
                'photo_url' => $e->photo_url,
                'documents' => $e->documents,
                'educations' => $e->educations,
                'trainings' => $e->trainings,
                'signature_url' => $e->latestSignature?->signature_url,
            ];

            return response()->json(['success' => true, 'message' => 'Success', 'data' => $data]);
        })->middleware('ability:employee');

        Route::post('/employee/profile/update', [EmployeeAuthController::class, 'updateProfile'])->middleware('ability:employee');
        Route::get('/employee/signature', [\App\Http\Controllers\Api\v1\Employee\EmployeeSignatureController::class, 'getSignature'])->middleware('ability:employee');
        Route::post('/employee/signature', [\App\Http\Controllers\Api\v1\Employee\EmployeeSignatureController::class, 'saveSignature'])->middleware('ability:employee');
        
        // Personal (Family, Education)
        Route::prefix('employee')->middleware('ability:employee')->group(function () {
            Route::get('/family', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'getFamily']);
            Route::post('/family', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'storeFamily']);
            Route::put('/family/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'updateFamily']);
            Route::delete('/family/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'deleteFamily']);
            
            Route::get('/education', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'getEducation']);
            Route::post('/education', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'storeEducation']);
            Route::put('/education/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'updateEducation']);
            Route::delete('/education/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'deleteEducation']);
        });

        Route::get('/employee/announcements', [\App\Http\Controllers\Api\v1\Admin\AnnouncementController::class, 'mobileIndex'])->middleware('ability:employee');
        Route::post('/employee/announcements/{id}/read', [\App\Http\Controllers\Api\v1\Admin\AnnouncementController::class, 'markAsRead'])->middleware('ability:employee');
        Route::get('/employee/dashboard/badges', [\App\Http\Controllers\Api\v1\Employee\BadgeController::class, 'getBadges'])->middleware('ability:employee');

        // ===== PHASE 4: ATTENDANCE & SHIFT & FACE (ability employee) =====
        Route::middleware('ability:employee')->group(function () {
            
            // EMPLOYEE PERSONAL (Family, Education, Cert, Doc)
            Route::prefix('personal')->group(function () {
                Route::get('/family', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'getFamily']);
                Route::post('/family', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'storeFamily']);
                Route::put('/family/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'updateFamily']);
                Route::delete('/family/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'deleteFamily']);

                Route::get('/education', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'getEducation']);
                Route::post('/education', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'storeEducation']);
                Route::put('/education/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'updateEducation']);
                Route::delete('/education/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'deleteEducation']);

                Route::get('/certification', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'getCertification']);
                Route::post('/certification', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'storeCertification']);
                Route::put('/certification/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'updateCertification']);
                Route::delete('/certification/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'deleteCertification']);

                Route::get('/document', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'getDocument']);
                Route::post('/document', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'storeDocument']);
                Route::delete('/document/{id}', [\App\Http\Controllers\Api\v1\Employee\EmployeePersonalController::class, 'deleteDocument']);
            });

            Route::get('/my-assets', [\App\Http\Controllers\AssetController::class, 'myAssets'])->name('api.assets.my');
            Route::get('/my-asset-requests', [\App\Http\Controllers\AssetRequestController::class, 'myRequests'])->name('api.asset-requests.my');
            Route::post('/my-asset-requests', [\App\Http\Controllers\AssetRequestController::class, 'store'])->name('api.asset-requests.my.store');

                // PHASE 3: ATTENDANCE
            Route::prefix('attendance')->group(function () {
                Route::get('/check-radius-permission', [AttendanceController::class, 'checkRadiusPermission'])->name('api.attendance.check-radius-permission');
                Route::get('/branch-locations', [AttendanceController::class, 'branchLocations'])->name('api.attendance.branch-locations');
                
                // Kiosk Attendance
                Route::post('/kiosk/verify-pin', [\App\Http\Controllers\Api\v1\Attendance\KioskController::class, 'verifyPin'])->withoutMiddleware('ability:employee')->middleware('ability:admin,employee');
                Route::post('/kiosk/clock-in', [\App\Http\Controllers\Api\v1\Attendance\KioskController::class, 'clockIn'])->withoutMiddleware('ability:employee')->middleware('ability:admin,employee');
                Route::post('/kiosk/clock-out', [\App\Http\Controllers\Api\v1\Attendance\KioskController::class, 'clockOut'])->withoutMiddleware('ability:employee')->middleware('ability:admin,employee');

                Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('api.attendance.clock-in');
                Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('api.attendance.clock-out');
                Route::post('/break', [AttendanceController::class, 'takeBreak'])->name('api.attendance.break');
                Route::post('/return-break', [AttendanceController::class, 'returnBreak'])->name('api.attendance.return-break');
                Route::get('/history', [AttendanceController::class, 'history'])->name('api.attendance.history');
                Route::post('/sync-location', [AttendanceController::class, 'syncLocation'])->name('api.attendance.sync-location');
                Route::post('/late', [\App\Http\Controllers\Api\v1\Leave\LateController::class, 'store'])->name('api.attendance.late');

                // Check Point
                Route::get('/check-points', [\App\Http\Controllers\Api\v1\Attendance\CheckPointController::class, 'index'])->name('api.attendance.check-points.index');
                Route::post('/check-points', [\App\Http\Controllers\Api\v1\Attendance\CheckPointController::class, 'store'])->name('api.attendance.check-points.store');
            });

            // FACE
            Route::prefix('face')->group(function () {
                Route::get('/', [FaceController::class, 'index'])->name('api.face.index');
                Route::post('/register', [FaceController::class, 'register'])->name('api.face.register');
                Route::post('/verify', [FaceController::class, 'verify'])->name('api.face.verify');
                Route::post('/liveness', [FaceController::class, 'liveness'])->name('api.face.liveness');
            });

            // ===== PHASE 5 (employee): LEAVE / IZIN / SAKIT / LATE / SWAP / LOAN =====
            Route::prefix('leave')->group(function () {
                Route::get('/balance', [\App\Http\Controllers\Api\v1\Leave\LeaveRequestController::class, 'balance'])->name('api.leave.balance');
                Route::get('/request', [\App\Http\Controllers\Api\v1\Leave\LeaveRequestController::class, 'index'])->name('api.leave.request.index');
                Route::post('/request', [\App\Http\Controllers\Api\v1\Leave\LeaveRequestController::class, 'store'])->name('api.leave.request.store');
            });

            Route::prefix('loan')->group(function () {
                Route::get('/request', [\App\Http\Controllers\Api\v1\Payroll\LoanRequestController::class, 'index'])->name('api.loan.index');
                Route::post('/request', [\App\Http\Controllers\Api\v1\Payroll\LoanRequestController::class, 'store'])->name('api.loan.store');
            });

            Route::prefix('permission')->group(function () {
                Route::get('/request', [\App\Http\Controllers\Api\v1\Leave\PermissionController::class, 'index'])->name('api.permission.index');
                Route::post('/request', [\App\Http\Controllers\Api\v1\Leave\PermissionController::class, 'store'])->name('api.permission.store');
            });

            Route::prefix('sick-leave')->group(function () {
                Route::get('/request', [\App\Http\Controllers\Api\v1\Leave\SickLeaveController::class, 'index'])->name('api.sick.index');
                Route::post('/request', [\App\Http\Controllers\Api\v1\Leave\SickLeaveController::class, 'store'])->name('api.sick.store');
            });

            Route::prefix('late')->group(function () {
                Route::get('/request', [\App\Http\Controllers\Api\v1\Leave\LateController::class, 'index'])->name('api.late.index');
                Route::post('/request', [\App\Http\Controllers\Api\v1\Leave\LateController::class, 'store'])->name('api.late.store');
            });

            Route::prefix('shift-swap')->group(function () {
                Route::get('/request', [\App\Http\Controllers\Api\v1\Leave\ShiftSwapController::class, 'index'])->name('api.shift-swap.index');
                Route::post('/request', [\App\Http\Controllers\Api\v1\Leave\ShiftSwapController::class, 'store'])->name('api.shift-swap.store');
                Route::post('/{shiftSwap}/confirm', [\App\Http\Controllers\Api\v1\Leave\ShiftSwapController::class, 'confirm'])->name('api.shift-swap.confirm');
            });

            Route::prefix('workday-swap')->group(function () {
                Route::get('/request', [\App\Http\Controllers\Api\v1\Leave\WorkdaySwapController::class, 'index'])->name('api.workday-swap.index');
                Route::post('/request', [\App\Http\Controllers\Api\v1\Leave\WorkdaySwapController::class, 'store'])->name('api.workday-swap.store');
            });

            // MOBILE OVERTIME (For Employee)
            Route::prefix('mobile/overtime')->group(function () {
                Route::get('/today', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'today']);
                Route::get('/history', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'history']);
                Route::post('/request', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'requestOvertime']);
                Route::post('/clock-in', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'clockIn']);
                Route::post('/clock-out', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'clockOut']);
                Route::post('/{id}/cancel', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'cancel']);
            });
            // Alias for mobile dashboard
            Route::get('/overtime/today', [\App\Http\Controllers\Api\v1\Mobile\MobileOvertimeController::class, 'today']);
        });

        // ===== MASTER (Phase 3) — ability admin =====
        Route::group(['middleware' => 'ability:admin'], function () {

            // Reports routes...
            Route::prefix('reports')->group(function () {
                Route::get('employees', [App\Http\Controllers\Api\v1\ReportController::class, 'employees']);
                Route::get('individual', [App\Http\Controllers\Api\v1\ReportController::class, 'individual']);
                Route::get('attendance', [App\Http\Controllers\Api\v1\ReportController::class, 'attendance']);
                Route::get('tardiness', [App\Http\Controllers\Api\v1\ReportController::class, 'tardiness']);
                Route::get('overtime', [App\Http\Controllers\Api\v1\ReportController::class, 'overtime']);
                Route::get('payroll', [App\Http\Controllers\Api\v1\ReportController::class, 'payroll'])->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class);
                Route::get('payroll/export-excel', [App\Http\Controllers\Api\v1\Payroll\PayrollController::class, 'exportExcelReport'])->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class);
                Route::get('leaves', [App\Http\Controllers\Api\v1\ReportController::class, 'leaves']);
                Route::get('loans', [App\Http\Controllers\Api\v1\ReportController::class, 'loans']);
                Route::get('kpi-summary', [App\Http\Controllers\Api\v1\ReportController::class, 'kpiSummary'])->middleware(\App\Http\Middleware\ForbidStaffKpiMiddleware::class);
                Route::get('violations', [App\Http\Controllers\Api\v1\ReportController::class, 'violations']);
            });

            // Dashboard Indicators
            Route::get('/dashboard/indicators', [\App\Http\Controllers\Api\v1\Master\DashboardController::class, 'indicators'])->name('api.dashboard.indicators');

            // Company Group
            Route::prefix('company-groups')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\v1\Master\CompanyGroupController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.company-groups.index');
                Route::post('/', [\App\Http\Controllers\Api\v1\Master\CompanyGroupController::class, 'store'])->name('api.company-groups.store');
                Route::get('/{companyGroup}', [\App\Http\Controllers\Api\v1\Master\CompanyGroupController::class, 'show'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.company-groups.show');
                Route::put('/{companyGroup}', [\App\Http\Controllers\Api\v1\Master\CompanyGroupController::class, 'update'])->name('api.company-groups.update');
                Route::delete('/{companyGroup}', [\App\Http\Controllers\Api\v1\Master\CompanyGroupController::class, 'destroy'])->name('api.company-groups.destroy');
            });

            // Company
            Route::prefix('companies')->group(function () {
                Route::get('/', [CompanyController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.companies.index');
                Route::post('/', [CompanyController::class, 'store'])->name('api.companies.store');
                Route::get('/{company}', [CompanyController::class, 'show'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.companies.show');
                Route::put('/{company}', [CompanyController::class, 'update'])->name('api.companies.update');
                Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('api.companies.destroy');
                
                Route::get('/{company}/approval-lines', [CompanyController::class, 'approvalLines'])->name('api.companies.approval-lines.index');
                Route::post('/{company}/approval-lines', [CompanyController::class, 'updateApprovalLines'])->name('api.companies.approval-lines.update');
                
                // Numberings
                Route::get('/{company}/numberings', [\App\Http\Controllers\CompanyNumberingController::class, 'index'])->name('api.companies.numberings.index');
                Route::post('/{company}/numberings', [\App\Http\Controllers\CompanyNumberingController::class, 'store'])->name('api.companies.numberings.store');
            });

            // Branch
            Route::prefix('branches')->group(function () {
                Route::get('/', [BranchController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.branches.index');
                Route::post('/', [BranchController::class, 'store'])->name('api.branches.store');
                Route::get('/{branch}', [BranchController::class, 'show'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.branches.show');
                Route::put('/{branch}', [BranchController::class, 'update'])->name('api.branches.update');
                Route::delete('/{branch}', [BranchController::class, 'destroy'])->name('api.branches.destroy');
            });

            // Department
            Route::prefix('departments')->group(function () {
                Route::get('/', [DepartmentController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.departments.index');
                Route::post('/', [DepartmentController::class, 'store'])->name('api.departments.store');
                Route::get('/{department}', [DepartmentController::class, 'show'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.departments.show');
                Route::put('/{department}', [DepartmentController::class, 'update'])->name('api.departments.update');
                Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('api.departments.destroy');
            });

            // Division
            Route::prefix('divisions')->group(function () {
                Route::get('/', [DivisionController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.divisions.index');
                Route::post('/', [DivisionController::class, 'store'])->name('api.divisions.store');
                Route::get('/{division}', [DivisionController::class, 'show'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.divisions.show');
                Route::put('/{division}', [DivisionController::class, 'update'])->name('api.divisions.update');
                Route::delete('/{division}', [DivisionController::class, 'destroy'])->name('api.divisions.destroy');
            });

            // Position
            Route::prefix('positions')->group(function () {
                Route::get('/', [PositionController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.positions.index');
                Route::post('/', [PositionController::class, 'store'])->name('api.positions.store');
                Route::get('/{position}', [PositionController::class, 'show'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.positions.show');
                Route::put('/{position}', [PositionController::class, 'update'])->name('api.positions.update');
                Route::delete('/{position}', [PositionController::class, 'destroy'])->name('api.positions.destroy');
            });


            // Employee Mutations
            Route::apiResource('employee-mutations', EmployeeMutationController::class)->except(['update', 'destroy']);
            Route::get('employee-mutations/{employee_mutation}/pdf', [EmployeeMutationController::class, 'exportPdf'])->name('api.employee-mutations.pdf');

            // Employee
            Route::prefix('employees')->group(function () {
                Route::get('/with-salary-structure', [EmployeeController::class, 'withSalaryStructure'])->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->name('api.employees.with-salary-structure');
                Route::get('/history', [EmployeeController::class, 'history'])->name('api.employees.history');
                Route::get('/', [EmployeeController::class, 'index'])->name('api.employees.index');
                Route::post('/', [EmployeeController::class, 'store'])->name('api.employees.store');
                Route::post('/{employee}/reactivate', [EmployeeController::class, 'reactivate'])->name('api.employees.reactivate');
                Route::get('/{employee}', [EmployeeController::class, 'show'])->name('api.employees.show');
                Route::get('/{employee}/details', [EmployeeController::class, 'details'])->name('api.employees.details');
                Route::put('/{employee}', [EmployeeController::class, 'update'])->name('api.employees.update');
                Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('api.employees.destroy');
                Route::get('/{employee}/simulate-payroll', [EmployeeController::class, 'simulatePayroll'])->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->name('api.employees.simulate-payroll');
                Route::get('/{employee}/assignments', [EmployeeController::class, 'getAssignments'])->name('api.employees.assignments');
                Route::put('/{employee}/mobile-access', [EmployeeController::class, 'approveMobileAccess'])->name('api.employees.mobile-access');
                Route::put('/{employee}/web-access', [EmployeeController::class, 'updateWebAccess'])->name('api.employees.web-access');
                // Device Lock Management
                Route::get('/{employee}/devices', [EmployeeController::class, 'deviceList'])->name('api.employees.devices.index');
                Route::post('/{employee}/devices/{deviceId}/approve', [EmployeeController::class, 'approveDevice'])->name('api.employees.devices.approve');
                Route::post('/{employee}/devices/{deviceId}/reject', [EmployeeController::class, 'rejectDevice'])->name('api.employees.devices.reject');
                // Documents, Education, Training
                Route::apiResource('{employee}/documents', EmployeeDocumentController::class)->only(['index', 'store', 'destroy']);
                Route::apiResource('{employee}/educations', EmployeeEducationController::class)->except(['show']);
                Route::apiResource('{employee}/trainings', EmployeeTrainingController::class)->except(['show']);
                // Approval Settings
                Route::get('/{employee}/approval-lines', [EmployeeApprovalSettingController::class, 'lines'])->name('api.employees.approval-lines.index');
                Route::post('/{employee}/approval-lines', [EmployeeApprovalSettingController::class, 'saveLines'])->name('api.employees.approval-lines.save');
                Route::delete('/{employee}/approval-lines', [EmployeeApprovalSettingController::class, 'deleteLines'])->name('api.employees.approval-lines.delete');
                // Delegation Settings
                Route::get('/{employee}/delegations', [EmployeeApprovalSettingController::class, 'delegations'])->name('api.employees.delegations.index');
                Route::post('/{employee}/delegations', [EmployeeApprovalSettingController::class, 'storeDelegation'])->name('api.employees.delegations.store');
                Route::put('/{employee}/delegations/{delegation}', [EmployeeApprovalSettingController::class, 'updateDelegation'])->name('api.employees.delegations.update');
                Route::delete('/{employee}/delegations/{delegation}', [EmployeeApprovalSettingController::class, 'destroyDelegation'])->name('api.employees.delegations.destroy');
            });

            // Assets
            Route::prefix('assets')->group(function () {
                Route::get('/', [\App\Http\Controllers\AssetController::class, 'index'])->name('api.assets.index');
                Route::post('/', [\App\Http\Controllers\AssetController::class, 'store'])->name('api.assets.store');
                Route::get('/generate-number', [\App\Http\Controllers\AssetController::class, 'generateNumber'])->name('api.assets.generate_number');
                Route::get('/{asset}', [\App\Http\Controllers\AssetController::class, 'show'])->name('api.assets.show');
                Route::put('/{asset}', [\App\Http\Controllers\AssetController::class, 'update'])->name('api.assets.update');
                Route::delete('/{asset}', [\App\Http\Controllers\AssetController::class, 'destroy'])->name('api.assets.destroy');
            });
            Route::apiResource('asset-types', \App\Http\Controllers\AssetTypeController::class);
            Route::get('/employees/{employee}/assets', [\App\Http\Controllers\AssetController::class, 'employeeAssets'])->name('api.employees.assets');

            // Asset Requests
            Route::prefix('asset-requests')->group(function () {
                Route::get('/', [\App\Http\Controllers\AssetRequestController::class, 'index'])->name('api.asset-requests.index');
                Route::post('/', [\App\Http\Controllers\AssetRequestController::class, 'store'])->name('api.asset-requests.store');
                Route::get('/my-requests', [\App\Http\Controllers\AssetRequestController::class, 'myRequests'])->name('api.asset-requests.my-requests');
                Route::get('/{id}', [\App\Http\Controllers\AssetRequestController::class, 'show'])->name('api.asset-requests.show');
                Route::put('/{id}', [\App\Http\Controllers\AssetRequestController::class, 'update'])->name('api.asset-requests.update');
                Route::delete('/{id}', [\App\Http\Controllers\AssetRequestController::class, 'destroy'])->name('api.asset-requests.destroy');
                Route::post('/{id}/purchase', [\App\Http\Controllers\AssetRequestController::class, 'purchase'])->name('api.asset-requests.purchase');
                Route::post('/{id}/status', [\App\Http\Controllers\AssetRequestController::class, 'updateStatus'])->name('api.asset-requests.status');
                Route::post('/{id}/approve-step', [\App\Http\Controllers\AssetRequestController::class, 'approveStep'])->name('api.asset-requests.approve-step');
            });

            // Asset Repairs
            Route::prefix('asset-repairs')->group(function () {
                Route::get('/', [\App\Http\Controllers\AssetRepairController::class, 'index'])->name('api.asset-repairs.index');
                Route::post('/', [\App\Http\Controllers\AssetRepairController::class, 'store'])->name('api.asset-repairs.store');
                Route::get('/{id}', [\App\Http\Controllers\AssetRepairController::class, 'show'])->name('api.asset-repairs.show');
                Route::put('/{id}', [\App\Http\Controllers\AssetRepairController::class, 'update'])->name('api.asset-repairs.update');
                Route::delete('/{id}', [\App\Http\Controllers\AssetRepairController::class, 'destroy'])->name('api.asset-repairs.destroy');
                Route::post('/{id}/status', [\App\Http\Controllers\AssetRepairController::class, 'updateStatus'])->name('api.asset-repairs.status');
            });

            // Asset Approval Settings
            Route::get('asset-approval-settings/by-category/{category}', [\App\Http\Controllers\AssetApprovalSettingController::class, 'getByCategory']);
            Route::post('asset-approval-settings/by-category/{category}', [\App\Http\Controllers\AssetApprovalSettingController::class, 'saveByCategory']);
            Route::apiResource('asset-approval-settings', \App\Http\Controllers\AssetApprovalSettingController::class);

            // System Settings
            Route::prefix('system-settings')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\v1\Master\SystemSettingController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Api\v1\Master\SystemSettingController::class, 'store']);
            });

            // PHASE 4: SHIFT MANAGEMENT
            Route::prefix('shifts')->group(function () {
                Route::get('/generate-code', [ShiftController::class, 'generateCode'])->name('api.shifts.generate-code');
                Route::get('/', [ShiftController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.shifts.index');
                Route::post('/', [ShiftController::class, 'store'])->name('api.shifts.store');
                Route::get('/{shift}', [ShiftController::class, 'show'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.shifts.show');
                Route::put('/{shift}', [ShiftController::class, 'update'])->name('api.shifts.update');
                Route::delete('/{shift}', [ShiftController::class, 'destroy'])->name('api.shifts.destroy');
            });

            // PHASE 4: EMPLOYEE SHIFT (schedule per employee)
            Route::prefix('employee-shifts')->group(function () {
                Route::get('/', [ShiftController::class, 'employeeShifts'])->name('api.employee-shifts.index');
                Route::post('/', [ShiftController::class, 'storeEmployeeShift'])->name('api.employee-shifts.store');
                Route::get('/patterns', [ShiftController::class, 'patterns'])->name('api.employee-shifts.patterns');
                Route::post('/patterns', [ShiftController::class, 'upsertPattern'])->name('api.employee-shifts.patterns.upsert');
                Route::post('/generate', [ShiftController::class, 'generateShifts'])->name('api.employee-shifts.generate');
                Route::post('/generate-all', [ShiftController::class, 'generateAllShifts'])->name('api.employee-shifts.generate-all');
                Route::get('/calendar', [ShiftController::class, 'calendar'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.employee-shifts.calendar');
                Route::get('/{employeeShift}', [ShiftController::class, 'showEmployeeShift'])->name('api.employee-shifts.show');
                Route::put('/{employeeShift}', [ShiftController::class, 'updateEmployeeShift'])->name('api.employee-shifts.update');
                Route::delete('/{employeeShift}', [ShiftController::class, 'destroyEmployeeShift'])->name('api.employee-shifts.destroy');
            });

            // OVERTIME MANAGEMENT
            Route::prefix('overtime-requests')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\v1\OvertimeRequestController::class, 'index']);
                Route::post('/{overtimeRequest}/approve', [\App\Http\Controllers\Api\v1\OvertimeRequestController::class, 'approve']);
                Route::post('/{overtimeRequest}/reject', [\App\Http\Controllers\Api\v1\OvertimeRequestController::class, 'reject']);
            });


            // Organization Chart
            Route::get('/organization-chart', [OrganizationChartController::class, 'index'])->name('api.organization-chart');

            // ===== PHASE 5 (admin): LEAVE TYPES CRUD =====
            Route::prefix('leave-types')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\v1\Leave\LeaveTypeController::class, 'index'])->name('api.leave-types.index');
                Route::post('/', [\App\Http\Controllers\Api\v1\Leave\LeaveTypeController::class, 'store'])->name('api.leave-types.store');
                Route::get('/{leaveType}', [\App\Http\Controllers\Api\v1\Leave\LeaveTypeController::class, 'show'])->name('api.leave-types.show');
                Route::put('/{leaveType}', [\App\Http\Controllers\Api\v1\Leave\LeaveTypeController::class, 'update'])->name('api.leave-types.update');
                Route::delete('/{leaveType}', [\App\Http\Controllers\Api\v1\Leave\LeaveTypeController::class, 'destroy'])->name('api.leave-types.destroy');
            });

            // ===== PHASE 5 (admin): LEAVE BALANCES CRUD =====
            Route::prefix('leave-balances')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\v1\Leave\LeaveBalanceController::class, 'index'])->name('api.leave-balances.index');
                Route::post('/', [\App\Http\Controllers\Api\v1\Leave\LeaveBalanceController::class, 'store'])->name('api.leave-balances.store');
                Route::get('/{leaveBalance}', [\App\Http\Controllers\Api\v1\Leave\LeaveBalanceController::class, 'show'])->name('api.leave-balances.show');
                Route::put('/{leaveBalance}', [\App\Http\Controllers\Api\v1\Leave\LeaveBalanceController::class, 'update'])->name('api.leave-balances.update');
                Route::delete('/{leaveBalance}', [\App\Http\Controllers\Api\v1\Leave\LeaveBalanceController::class, 'destroy'])->name('api.leave-balances.destroy');
            });

            // ===== PHASE 5 (admin): APPROVAL ENGINE =====
            Route::prefix('approval')->group(function () {
                Route::get('/workflows', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'workflows'])->name('api.approval.workflows');
                Route::post('/workflows', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'storeWorkflow'])->name('api.approval.workflows.store');
                Route::get('/workflows/{workflow}', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'showWorkflow'])->name('api.approval.workflows.show');
                Route::put('/workflows/{workflow}', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'updateWorkflow'])->name('api.approval.workflows.update');
                Route::delete('/workflows/{workflow}', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'destroyWorkflow'])->name('api.approval.workflows.destroy');
                Route::get('/steps', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'steps'])->name('api.approval.steps');
                Route::post('/steps', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'storeStep'])->name('api.approval.steps.store');
                Route::delete('/steps/{step}', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'destroyStep'])->name('api.approval.steps.destroy');
                Route::get('/requests', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'requests'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.approval.requests');
                Route::get('/requests/{approval}', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'showRequest'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.approval.requests.show');
                Route::post('/requests/{approval}/approve', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'approve'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.approval.requests.approve');
                Route::post('/requests/{approval}/reject', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'reject'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.approval.requests.reject');
                Route::post('/requests/{approval}/cancel', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'cancel'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.approval.requests.cancel');
                Route::delete('/requests/{approval}', [\App\Http\Controllers\Api\v1\Approval\ApprovalEngineController::class, 'destroyRequest'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.approval.requests.destroy');
            });

            // REIMBURSEMENT
            Route::prefix('reimbursements')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\v1\Reimbursement\ReimbursementController::class, 'index'])->name('api.reimbursements.index');
                Route::post('/', [\App\Http\Controllers\Api\v1\Reimbursement\ReimbursementController::class, 'store'])->name('api.reimbursements.store');
                Route::get('/{reimbursement}', [\App\Http\Controllers\Api\v1\Reimbursement\ReimbursementController::class, 'show'])->name('api.reimbursements.show');
                Route::put('/{reimbursement}', [\App\Http\Controllers\Api\v1\Reimbursement\ReimbursementController::class, 'update'])->name('api.reimbursements.update');
                Route::delete('/{reimbursement}', [\App\Http\Controllers\Api\v1\Reimbursement\ReimbursementController::class, 'destroy'])->name('api.reimbursements.destroy');
                Route::put('/{reimbursement}/approve', [\App\Http\Controllers\Api\v1\Reimbursement\ReimbursementController::class, 'approve'])->name('api.reimbursements.approve');
            });

            // CANVASSING
            Route::prefix('canvassing')->group(function () {
                Route::get('/live', [\App\Http\Controllers\Api\v1\Attendance\CanvassingController::class, 'liveLocations'])->name('api.canvassing.live');
                Route::get('/history', [\App\Http\Controllers\Api\v1\Attendance\CanvassingController::class, 'locationHistory'])->name('api.canvassing.history');
            });

            // CHECK POINTS (ADMIN)
            Route::prefix('check-points')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\v1\Attendance\CheckPointController::class, 'index'])->name('api.check-points.admin.index');
            });
        });

        // ===== PHASE 6: FAMILY / DOCUMENTS / REWARD / WARNING / TERMINATION / EXIT CLEARANCE =====
        Route::group(['middleware' => 'ability:admin'], function () {
                // 6A. FAMILY
                Route::prefix('families')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'index'])->name('api.families.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'store'])->name('api.families.store');
                    Route::get('/{family}', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'show'])->name('api.families.show');
                    Route::put('/{family}', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'update'])->name('api.families.update');
                    Route::delete('/{family}', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'destroy'])->name('api.families.destroy');
                });

                // 6B. DOCUMENTS
                Route::prefix('documents')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'index'])->name('api.documents.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'store'])->name('api.documents.store');
                    Route::get('/{document}', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'show'])->name('api.documents.show');
                    Route::put('/{document}', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'update'])->name('api.documents.update');
                    Route::delete('/{document}', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'destroy'])->name('api.documents.destroy');
                });

                // 6C. REWARD
                Route::prefix('rewards')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\RewardController::class, 'index'])->name('api.rewards.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase6\RewardController::class, 'store'])->name('api.rewards.store');
                    Route::get('/{reward}', [\App\Http\Controllers\Api\v1\Phase6\RewardController::class, 'show'])->name('api.rewards.show');
                    Route::put('/{reward}', [\App\Http\Controllers\Api\v1\Phase6\RewardController::class, 'update'])->name('api.rewards.update');
                    Route::delete('/{reward}', [\App\Http\Controllers\Api\v1\Phase6\RewardController::class, 'destroy'])->name('api.rewards.destroy');
                });

                // RECURRING INCENTIVES
                Route::prefix('recurring-incentives')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\EmployeeRecurringIncentiveController::class, 'index'])->name('api.recurring-incentives.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase6\EmployeeRecurringIncentiveController::class, 'store'])->name('api.recurring-incentives.store');
                    Route::get('/{employeeRecurringIncentive}', [\App\Http\Controllers\Api\v1\Phase6\EmployeeRecurringIncentiveController::class, 'show'])->name('api.recurring-incentives.show');
                    Route::put('/{employeeRecurringIncentive}', [\App\Http\Controllers\Api\v1\Phase6\EmployeeRecurringIncentiveController::class, 'update'])->name('api.recurring-incentives.update');
                    Route::delete('/{employeeRecurringIncentive}', [\App\Http\Controllers\Api\v1\Phase6\EmployeeRecurringIncentiveController::class, 'destroy'])->name('api.recurring-incentives.destroy');
                });

                // PAYROLL DEDUCTIONS (POTONGAN LAIN-LAIN)
                Route::prefix('payroll-deductions')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Payroll\PayrollDeductionController::class, 'index'])->name('api.payroll-deductions.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Payroll\PayrollDeductionController::class, 'store'])->name('api.payroll-deductions.store');
                    Route::get('/{payrollDeduction}', [\App\Http\Controllers\Api\v1\Payroll\PayrollDeductionController::class, 'show'])->name('api.payroll-deductions.show');
                    Route::put('/{payrollDeduction}', [\App\Http\Controllers\Api\v1\Payroll\PayrollDeductionController::class, 'update'])->name('api.payroll-deductions.update');
                    Route::delete('/{payrollDeduction}', [\App\Http\Controllers\Api\v1\Payroll\PayrollDeductionController::class, 'destroy'])->name('api.payroll-deductions.destroy');
                });


                // 6D. WARNING / SP
                Route::prefix('warnings')->group(function () {
                    Route::get('/flagged', [\App\Http\Controllers\Api\v1\Phase6\WarningController::class, 'flaggedCandidates'])->name('api.warnings.flagged');
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\WarningController::class, 'index'])->name('api.warnings.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase6\WarningController::class, 'store'])->name('api.warnings.store');
                    Route::get('/{warning}', [\App\Http\Controllers\Api\v1\Phase6\WarningController::class, 'show'])->name('api.warnings.show');
                    Route::put('/{warning}', [\App\Http\Controllers\Api\v1\Phase6\WarningController::class, 'update'])->name('api.warnings.update');
                    Route::delete('/{warning}', [\App\Http\Controllers\Api\v1\Phase6\WarningController::class, 'destroy'])->name('api.warnings.destroy');
                    Route::get('/{warning}/pdf', [\App\Http\Controllers\Api\v1\Phase6\WarningController::class, 'downloadPdf'])->name('api.warnings.pdf');
                });

                // 6E. TERMINATION
                Route::prefix('terminations')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\TerminationController::class, 'index'])->name('api.terminations.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase6\TerminationController::class, 'store'])->name('api.terminations.store');
                    Route::get('/{termination}', [\App\Http\Controllers\Api\v1\Phase6\TerminationController::class, 'show'])->name('api.terminations.show');
                    Route::put('/{termination}', [\App\Http\Controllers\Api\v1\Phase6\TerminationController::class, 'update'])->name('api.terminations.update');
                    Route::delete('/{termination}', [\App\Http\Controllers\Api\v1\Phase6\TerminationController::class, 'destroy'])->name('api.terminations.destroy');
                    Route::post('/{termination}/approve', [\App\Http\Controllers\Api\v1\Phase6\TerminationController::class, 'approve'])->name('api.terminations.approve');
                    Route::get('/{termination}/pdf', [\App\Http\Controllers\Api\v1\Phase6\TerminationController::class, 'downloadPdf'])->name('api.terminations.pdf');
                });

                // 6F. EXIT CLEARANCE
                Route::prefix('exit-clearances')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\ExitClearanceController::class, 'index'])->name('api.exit-clearances.index');
                    Route::get('/{clearance}', [\App\Http\Controllers\Api\v1\Phase6\ExitClearanceController::class, 'show'])->name('api.exit-clearances.show');
                    Route::delete('/{clearance}', [\App\Http\Controllers\Api\v1\Phase6\ExitClearanceController::class, 'destroy'])->name('api.exit-clearances.destroy');
                    Route::post('/{termination}/items', [\App\Http\Controllers\Api\v1\Phase6\ExitClearanceController::class, 'storeItem'])->name('api.exit-clearances.store-item');
                    Route::put('/{termination}/items/{item}/check', [\App\Http\Controllers\Api\v1\Phase6\ExitClearanceController::class, 'checkItem'])->name('api.exit-clearances.check-item');
                });
            });

            // ===== PHASE 6 (employee): data milik sendiri =====
            Route::group(['middleware' => 'ability:employee'], function () {
                // 6A. FAMILY (prefix /family singular)
                Route::prefix('family')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'myIndex'])->name('api.family.my-index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'myStore'])->name('api.family.my-store');
                    Route::put('/{family}', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'myUpdate'])->name('api.family.my-update');
                    Route::delete('/{family}', [\App\Http\Controllers\Api\v1\Phase6\FamilyController::class, 'myDestroy'])->name('api.family.my-destroy');
                });

                // 6B. DOCUMENTS (prefix /my/documents) — avoid collision dgn admin /documents
                Route::prefix('my/documents')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'myIndex'])->name('api.my-documents.my-index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'myStore'])->name('api.my-documents.my-store');
                    Route::put('/{document}', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'myUpdate'])->name('api.my-documents.my-update');
                    Route::delete('/{document}', [\App\Http\Controllers\Api\v1\Phase6\DocumentController::class, 'myDestroy'])->name('api.my-documents.my-destroy');
                });

                // 6C. REWARD (GET sendiri)
                Route::get('/my/rewards', [\App\Http\Controllers\Api\v1\Phase6\RewardController::class, 'myIndex'])->name('api.my-rewards.my-index');
                Route::get('/my/deductions', [\App\Http\Controllers\Api\v1\Payroll\PayrollDeductionController::class, 'myIndex'])->name('api.my-deductions.my-index');

                // 6D. WARNING (GET sendiri)
                Route::get('/my/warnings', [\App\Http\Controllers\Api\v1\Phase6\WarningController::class, 'myIndex'])->name('api.my-warnings.my-index');

                // 6E. TERMINATION (GET sendiri)
                Route::get('/my/terminations', [\App\Http\Controllers\Api\v1\Phase6\TerminationController::class, 'myIndex'])->name('api.my-terminations.my-index');

                // 6F. EXIT CLEARANCE (status sendiri)
                Route::get('/my/exit-clearances', [\App\Http\Controllers\Api\v1\Phase6\ExitClearanceController::class, 'myIndex'])->name('api.my-exit-clearances.my-index');
            });

            // ===== PHASE 7: TASK MANAGEMENT =====
            Route::group(['middleware' => 'ability:admin'], function () {
                Route::prefix('tasks')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'index'])->name('api.tasks.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'store'])->name('api.tasks.store');
                    Route::get('/{task}', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'show'])->name('api.tasks.show');
                    Route::put('/{task}', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'update'])->name('api.tasks.update');
                    Route::delete('/{task}', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'destroy'])->name('api.tasks.destroy');
                    Route::post('/{task}/assign', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'assign'])->name('api.tasks.assign');
                    Route::post('/{task}/progress', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'progress'])->name('api.tasks.progress');
                    Route::put('/{task}/status', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'updateStatus'])->name('api.tasks.status');
                });
            });

            // ===== PHASE 7 (employee): MY TASKS =====
            Route::group(['middleware' => 'ability:employee'], function () {
                Route::prefix('my/tasks')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'myIndex'])->name('api.my-tasks.index');
                    Route::post('/batch', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'myStoreBatch'])->name('api.my-tasks.store-batch');
                    Route::post('/complete-all', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'myCompleteAll'])->name('api.my-tasks.complete-all');
                    Route::put('/{task}/status', [\App\Http\Controllers\Api\v1\Phase7\TaskController::class, 'myUpdateStatus'])->name('api.my-tasks.status');
                });
            });

            // ===================== PHASE 8: PAYROLL + PAJAK + BPJS + SLIP GAJI =====================
            Route::group(['middleware' => 'ability:admin'], function () {

                // 8A. KOMPONEN GAJI
                Route::prefix('payroll-components')->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->group(function () {
                    Route::get('/', [PayrollComponentController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.payroll-components.index');
                    Route::post('/', [PayrollComponentController::class, 'store'])->name('api.payroll-components.store');
                    Route::get('/{component}', [PayrollComponentController::class, 'show'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.payroll-components.show');
                    Route::put('/{component}', [PayrollComponentController::class, 'update'])->name('api.payroll-components.update');
                    Route::delete('/{component}', [PayrollComponentController::class, 'destroy'])->name('api.payroll-components.destroy');
                });

                // ADMIN ATTENDANCE
                Route::prefix('attendance/admin')->group(function () {
                    Route::get('/history', [\App\Http\Controllers\Api\v1\Attendance\AdminAttendanceController::class, 'history'])->name('api.attendance.admin.history');
                    Route::get('/daily', [\App\Http\Controllers\Api\v1\Attendance\AdminAttendanceController::class, 'daily'])->name('api.attendance.admin.daily');
                    Route::post('/manual', [\App\Http\Controllers\Api\v1\Attendance\AdminAttendanceController::class, 'store'])->name('api.attendance.admin.store');
                });

                // ADMIN LEAVE REQUESTS
                Route::get('/leave-requests/admin', [\App\Http\Controllers\Api\v1\Leave\LeaveRequestController::class, 'adminIndex'])->name('api.leave-requests.admin');

                // ADMIN LOAN REQUESTS
                Route::get('/loan-requests/admin', [\App\Http\Controllers\Api\v1\Admin\AdminLoanRequestController::class, 'index'])->name('api.loan-requests.admin.index');
                Route::put('/loan-requests/admin/{loanRequest}/installments', [\App\Http\Controllers\Api\v1\Admin\AdminLoanRequestController::class, 'updateInstallment'])->name('api.loan-requests.admin.update-installment');
                Route::get('/loan-requests/admin/{loanRequest}/repayments', [\App\Http\Controllers\Api\v1\Admin\AdminLoanRequestController::class, 'repayments'])->name('api.loan-requests.admin.repayments');
                Route::post('/loan-requests/admin/{loanRequest}/approve', [\App\Http\Controllers\Api\v1\Admin\AdminLoanRequestController::class, 'approve'])->name('api.loan-requests.admin.approve');
                Route::post('/loan-requests/admin/{loanRequest}/reject', [\App\Http\Controllers\Api\v1\Admin\AdminLoanRequestController::class, 'reject'])->name('api.loan-requests.admin.reject');


                // 8B. STRUKTUR GAJI KARYAWAN
                Route::prefix('salary-structures')->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->group(function () {
                    Route::get('/', [SalaryStructureController::class, 'index'])->name('api.salary-structures.index');
                    Route::post('/', [SalaryStructureController::class, 'store'])->name('api.salary-structures.store'); // batch items
                    Route::post('/{employee}/item', [SalaryStructureController::class, 'storeItem'])->name('api.salary-structures.store-item'); // satu item
                    Route::get('/{salaryStructure}', [SalaryStructureController::class, 'show'])->name('api.salary-structures.show');
                    Route::put('/{salaryStructure}', [SalaryStructureController::class, 'update'])->name('api.salary-structures.update');
                    Route::delete('/{salaryStructure}', [SalaryStructureController::class, 'destroy'])->name('api.salary-structures.destroy');
                });
                
                Route::apiResource('employee-loans', \App\Http\Controllers\Api\v1\Payroll\EmployeeLoanController::class, [
                    'as' => 'api'
                ]);


                // 8E. SETTING PTKP
                Route::prefix('ptkp-settings')->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->group(function () {
                    Route::get('/', [PtkpSettingController::class, 'index'])->name('api.ptkp-settings.index');
                    Route::post('/', [PtkpSettingController::class, 'store'])->name('api.ptkp-settings.store');
                    Route::get('/{ptkpSetting}', [PtkpSettingController::class, 'show'])->name('api.ptkp-settings.show');
                    Route::put('/{ptkpSetting}', [PtkpSettingController::class, 'update'])->name('api.ptkp-settings.update');
                    Route::delete('/{ptkpSetting}', [PtkpSettingController::class, 'destroy'])->name('api.ptkp-settings.destroy');
                });

                // 8F. PERIODE + GENERATE + PROCESS + SUMMARY
                Route::prefix('payroll-rules')->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->group(function () {
                    Route::get('/variables', [\App\Http\Controllers\Api\v1\Admin\PayrollVariableController::class, 'index'])->name('api.payroll-rules.variables');
                    Route::get('/', [\App\Http\Controllers\Api\v1\Admin\PayrollRuleController::class, 'index'])->withoutMiddleware('ability:admin')->middleware('ability:admin,employee')->name('api.payroll-rules.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Admin\PayrollRuleController::class, 'store'])->name('api.payroll-rules.store');
                    Route::get('/{payrollRule}', [\App\Http\Controllers\Api\v1\Admin\PayrollRuleController::class, 'show'])->name('api.payroll-rules.show');
                    Route::put('/{payrollRule}', [\App\Http\Controllers\Api\v1\Admin\PayrollRuleController::class, 'update'])->name('api.payroll-rules.update');
                    Route::delete('/{payrollRule}', [\App\Http\Controllers\Api\v1\Admin\PayrollRuleController::class, 'destroy'])->name('api.payroll-rules.destroy');
                });

                Route::prefix('payroll-inputs')->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Admin\PayrollInputController::class, 'index'])->name('api.payroll-inputs.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Admin\PayrollInputController::class, 'store'])->name('api.payroll-inputs.store');
                    Route::get('/{payrollInput}', [\App\Http\Controllers\Api\v1\Admin\PayrollInputController::class, 'show'])->name('api.payroll-inputs.show');
                    Route::put('/{payrollInput}', [\App\Http\Controllers\Api\v1\Admin\PayrollInputController::class, 'update'])->name('api.payroll-inputs.update');
                    Route::delete('/{payrollInput}', [\App\Http\Controllers\Api\v1\Admin\PayrollInputController::class, 'destroy'])->name('api.payroll-inputs.destroy');
                });

                Route::prefix('employee-salary-structures')->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Admin\EmployeeSalaryStructureController::class, 'index'])->name('api.employee-salary-structures.index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Admin\EmployeeSalaryStructureController::class, 'store'])->name('api.employee-salary-structures.store');
                    Route::get('/{employeeSalaryStructure}', [\App\Http\Controllers\Api\v1\Admin\EmployeeSalaryStructureController::class, 'show'])->name('api.employee-salary-structures.show');
                    Route::put('/{employeeSalaryStructure}', [\App\Http\Controllers\Api\v1\Admin\EmployeeSalaryStructureController::class, 'update'])->name('api.employee-salary-structures.update');
                });
                Route::apiResource('employee-salary-structures', \App\Http\Controllers\Api\v1\Admin\EmployeeSalaryStructureController::class)->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class);
                Route::apiResource('announcements', \App\Http\Controllers\Api\v1\Admin\AnnouncementController::class)->only(['index', 'store', 'destroy']);

                Route::prefix('payroll')->middleware(\App\Http\Middleware\ForbidStaffPayrollMiddleware::class)->group(function () {
                    Route::get('/periods', [PayrollController::class, 'indexPeriods'])->name('api.payroll.periods.index');
                    Route::post('/periods', [PayrollController::class, 'storePeriod'])->name('api.payroll.periods.store');
                    // Auto-generate: buat periode dengan nama otomatis berdasarkan cycle_type & tanggal referensi
                    Route::post('/periods/auto-generate', [PayrollController::class, 'autoGeneratePeriod'])->name('api.payroll.periods.auto-generate');
                    Route::post('/generate', [PayrollController::class, 'generate'])->name('api.payroll.generate');
                    Route::post('/simulate/{employee}', [PayrollController::class, 'simulate'])->name('api.payroll.simulate');
                    Route::post('/periods/{period}/process', [PayrollController::class, 'process'])->name('api.payroll.periods.process');
                    Route::post('/periods/{period}/complete', [PayrollController::class, 'complete'])->name('api.payroll.periods.complete');
                    Route::post('/periods/{period}/close', [PayrollController::class, 'close'])->name('api.payroll.periods.close');
                    Route::post('/periods/{period}/cancel', [PayrollController::class, 'cancelPeriod'])->name('api.payroll.periods.cancel');
                    Route::get('/periods/{period}', [PayrollController::class, 'showPeriod'])->name('api.payroll.periods.show');
                    Route::get('/periods/{period}/export-excel', [PayrollController::class, 'exportExcelReport'])->name('api.payroll.periods.export-excel');
                    Route::get('/export-report', [PayrollController::class, 'exportExcelReport'])->name('api.payroll.export-report');
                    Route::put('/periods/{period}', [PayrollController::class, 'updatePeriod'])->name('api.payroll.periods.update');
                    Route::delete('/periods/{period}', [PayrollController::class, 'destroyPeriod'])->name('api.payroll.periods.destroy');
                    Route::get('/runs', [PayrollController::class, 'runs'])->name('api.payroll.runs.index');
                    Route::get('/runs/{run}', [PayrollController::class, 'showRun'])->name('api.payroll.runs.show');
                    Route::get('/runs/{run}/pdf', [PayrollController::class, 'runPdf'])->name('api.payroll.runs.pdf');
                    Route::post('/runs/{run}/send-email', [PayrollController::class, 'sendPayslipEmail'])->name('api.payroll.runs.send-email');
                    Route::post('/send-email-bulk', [PayrollController::class, 'sendBulkPayslipEmail'])->name('api.payroll.runs.send-email-bulk');
                    // Monitoring: cek apakah karyawan sudah digaji atau belum, termasuk status email
                    Route::get('/control-monitoring', [PayrollController::class, 'monitoringControl'])->name('api.payroll.monitoring');
                    Route::get('/summary', [PayrollController::class, 'summary'])->name('api.payroll.summary');
                });
            });

            // ===== PHASE 8 (employee): MY PAYSLIPS =====
            Route::group(['middleware' => 'ability:employee'], function () {
                Route::prefix('my/payslips')->group(function () {
                    Route::get('/', [PayrollController::class, 'myPayslips'])->name('api.my-payslips.index');
                    Route::get('/{run}', [PayrollController::class, 'myPayslip'])->name('api.my-payslips.show');
                    Route::get('/{run}/pdf', [PayrollController::class, 'myPayslipPdf'])->name('api.my-payslips.pdf');
                });
            });

            // ===================== PHASE 9: NOTIFIKASI + FCM DEVICE TOKEN =====================

            // EMPLOYEE: my notifications + device tokens
            Route::group(['middleware' => 'ability:employee'], function () {
                // Reimbursements
                Route::prefix('my/reimbursements')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\v1\Reimbursement\ReimbursementController::class, 'myIndex'])->name('api.my-reimbursements.my-index');
                    Route::post('/', [\App\Http\Controllers\Api\v1\Reimbursement\ReimbursementController::class, 'myStore'])->name('api.my-reimbursements.my-store');
                });

                Route::prefix('my/notifications')->group(function () {
                    Route::get('/', [NotificationController::class, 'myIndex'])->name('api.my-notifications.index');
                    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('api.my-notifications.unread-count');
                    Route::put('/read-all', [NotificationController::class, 'markAllRead'])->name('api.my-notifications.read-all');
                    Route::put('/{notification}/read', [NotificationController::class, 'markRead'])->name('api.my-notifications.read');
                    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('api.my-notifications.destroy');
                });

                Route::post('/my/device-tokens', [NotificationController::class, 'registerDeviceToken'])->name('api.my-device-tokens.register');
            });

            // ADMIN: send, list, broadcast
            Route::group(['middleware' => 'ability:admin'], function () {
                Route::prefix('notifications')->group(function () {
                    Route::get('/', [NotificationController::class, 'index'])->name('api.notifications.index');
                    Route::post('/send', [NotificationController::class, 'send'])->name('api.notifications.send');
                    Route::post('/broadcast', [NotificationController::class, 'broadcast'])->name('api.notifications.broadcast');
                });
            });

            // ===================== RMS (Recruitment) — ADMIN (ability:admin) =====================
            Route::group(['middleware' => 'ability:admin'], function () {
                Route::prefix('recruitment/admin')->group(function () {
                Route::get('/jobs', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'jobs'])->name('api.recruitment.admin.jobs');
                Route::post('/jobs', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'storeJob'])->name('api.recruitment.admin.jobs.store');
                Route::get('/jobs/{job}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'showJob'])->name('api.recruitment.admin.jobs.show');
                Route::put('/jobs/{job}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'updateJob'])->name('api.recruitment.admin.jobs.update');
                Route::delete('/jobs/{job}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'destroyJob'])->name('api.recruitment.admin.jobs.destroy');

                Route::get('/tests', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'tests'])->name('api.recruitment.admin.tests');
                Route::post('/tests', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'storeTest'])->name('api.recruitment.admin.tests.store');
                Route::get('/tests/{test}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'showTest'])->name('api.recruitment.admin.tests.show');
                Route::put('/tests/{test}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'updateTest'])->name('api.recruitment.admin.tests.update');
                Route::delete('/tests/{test}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'destroyTest'])->name('api.recruitment.admin.tests.destroy');

                Route::post('/tests/{test}/questions', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'storeQuestion'])->name('api.recruitment.admin.tests.questions.store');
                Route::put('/tests/{test}/questions/{question}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'updateQuestion'])->name('api.recruitment.admin.tests.questions.update');
                Route::delete('/tests/{test}/questions/{question}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'destroyQuestion'])->name('api.recruitment.admin.tests.questions.destroy');

                Route::get('/candidates', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'candidates'])->name('api.recruitment.admin.candidates');
                Route::get('/candidates/{candidate}', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'candidateDetail'])->name('api.recruitment.admin.candidates.show');
                Route::post('/candidates/{candidate}/status', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'updateCandidateStatus'])->name('api.recruitment.admin.candidates.status');
                Route::put('/candidates/{candidate}/documents/{document}/verify', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'verifyDocument'])->name('api.recruitment.admin.candidates.documents.verify');

                Route::post('/applications/{application}/invite-test', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'inviteTest'])->name('api.recruitment.admin.applications.invite-test');
                Route::post('/applications/{application}/schedule-interview', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'scheduleInterview'])->name('api.recruitment.admin.applications.schedule-interview');
                Route::post('/applications/{application}/interviews/{interview}/result', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'interviewResult'])->name('api.recruitment.admin.applications.interviews.result');
                Route::post('/applications/{application}/hire', [\App\Http\Controllers\Api\v1\Recruitment\AdminRecruitmentController::class, 'hire'])->name('api.recruitment.admin.applications.hire');
                });

                // ===================== CMS (Company Profile) — ADMIN (ability:admin) =====================
                Route::prefix('cms')->group(function () {
                Route::get('/settings', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'settings'])->name('api.cms.admin.settings');
                Route::post('/settings', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'upsertSetting'])->name('api.cms.admin.settings.upsert');
                Route::get('/settings/{key}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'showSetting'])->name('api.cms.admin.settings.show');
                Route::delete('/settings/{key}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'destroySetting'])->name('api.cms.admin.settings.destroy');

                Route::get('/pages', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'pages'])->name('api.cms.admin.pages');
                Route::post('/pages', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'storePage'])->name('api.cms.admin.pages.store');
                Route::get('/pages/{page}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'showPage'])->name('api.cms.admin.pages.show');
                Route::put('/pages/{page}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'updatePage'])->name('api.cms.admin.pages.update');
                Route::delete('/pages/{page}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'destroyPage'])->name('api.cms.admin.pages.destroy');

                Route::get('/sections', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'sections'])->name('api.cms.admin.sections');
                Route::post('/sections', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'storeSection'])->name('api.cms.admin.sections.store');
                Route::get('/sections/{section}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'showSection'])->name('api.cms.admin.sections.show');
                Route::put('/sections/{section}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'updateSection'])->name('api.cms.admin.sections.update');
                Route::delete('/sections/{section}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'destroySection'])->name('api.cms.admin.sections.destroy');

                Route::get('/media', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'media'])->name('api.cms.admin.media');
                Route::post('/media', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'storeMedia'])->name('api.cms.admin.media.store');
                Route::delete('/media/{media}', [\App\Http\Controllers\Api\v1\Cms\AdminCmsController::class, 'destroyMedia'])->name('api.cms.admin.media.destroy');
                Route::get('/products', [\App\Http\Controllers\Api\v1\Admin\CmsProductController::class, 'index'])->name('api.cms.admin.products.index');
                Route::post('/products', [\App\Http\Controllers\Api\v1\Admin\CmsProductController::class, 'store'])->name('api.cms.admin.products.store');
                Route::get('/products/{cmsProduct}', [\App\Http\Controllers\Api\v1\Admin\CmsProductController::class, 'show'])->name('api.cms.admin.products.show');
                Route::put('/products/{cmsProduct}', [\App\Http\Controllers\Api\v1\Admin\CmsProductController::class, 'update'])->name('api.cms.admin.products.update');
                Route::delete('/products/{cmsProduct}', [\App\Http\Controllers\Api\v1\Admin\CmsProductController::class, 'destroy'])->name('api.cms.admin.products.destroy');

                // Custom CMS Content
                Route::get('/hero', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'getHero']);
                Route::post('/hero', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'updateHero']);

                Route::get('/about', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'getAbout']);
                Route::post('/about', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'updateAbout']);
                
                Route::get('/advantages', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'getAdvantages']);
                Route::post('/advantages', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'storeAdvantage']);
                Route::put('/advantages/{id}', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'updateAdvantage']);
                Route::delete('/advantages/{id}', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'destroyAdvantage']);
                
                Route::get('/marketplaces', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'getMarketplaces']);
                Route::post('/marketplaces', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'updateMarketplaces']);
                
                Route::get('/contact', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'getContact']);
                Route::post('/contact', [\App\Http\Controllers\Api\v1\Admin\CmsContentController::class, 'updateContact']);
                });

                // ===== KPI (HANYA Manager, Supervisor & Admin — tidak boleh di level staff) =====
                Route::prefix('kpi')->middleware(\App\Http\Middleware\ForbidStaffKpiMiddleware::class)->group(function () {
                    Route::apiResource('indicators', \App\Http\Controllers\Api\v1\Kpi\KpiIndicatorController::class)->parameters(['indicators' => 'kpiIndicator']);
                    Route::apiResource('rating-scales', \App\Http\Controllers\Api\v1\Kpi\KpiRatingScaleController::class)->parameters(['rating-scales' => 'kpiRatingScale']);
                    Route::apiResource('templates', \App\Http\Controllers\Api\v1\Kpi\KpiTemplateController::class)->parameters(['templates' => 'kpiTemplate']);
                    Route::apiResource('periods', \App\Http\Controllers\Api\v1\Kpi\KpiPeriodController::class)->parameters(['periods' => 'kpiPeriod']);
                    Route::post('periods/{kpiPeriod}/generate-assessments', [\App\Http\Controllers\Api\v1\Kpi\KpiPeriodController::class, 'generateAssessments']);
                    Route::get('assessments', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'index']);
                    Route::post('assessments', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'store']);
                    Route::get('dashboard-analytics', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'dashboardAnalytics']);
                    Route::get('assessments/report', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'report']);
                    Route::get('assessments/report/pdf', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'exportReportPdf']);
                    Route::get('assessments/{kpiAssessment}', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'show']);
                    Route::put('assessments/{kpiAssessment}', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'update']);
                    Route::delete('assessments/{kpiAssessment}', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'destroy']);
                    Route::get('assessments/{kpiAssessment}/pdf', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'exportSinglePdf']);
                    Route::post('assessments/{kpiAssessment}/score', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'submitScore']);
                    Route::post('assessments/{kpiAssessment}/approve', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'approve']);
                    Route::post('assessments/{kpiAssessment}/reset', [\App\Http\Controllers\Api\v1\Kpi\KpiAssessmentController::class, 'reset']);

                    // Probation KPI & Salary Proposals
                    Route::prefix('probation')->group(function () {
                        Route::get('/', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'index']);
                        Route::post('/', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'store']);
                        Route::get('/employee-summary/{employee}', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'employeeSummary']);
                        Route::get('/{probationEvaluation}', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'show']);
                        Route::put('/{probationEvaluation}', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'update']);
                        Route::delete('/{probationEvaluation}', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'destroy']);
                        Route::post('/{probationEvaluation}/salary-proposal', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'saveSalaryProposal']);
                        Route::post('/{probationEvaluation}/approve', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'approve']);
                        Route::get('/{probationEvaluation}/export-evaluation-pdf', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'exportEvaluationPdf']);
                        Route::get('/{probationEvaluation}/export-salary-pdf', [\App\Http\Controllers\Api\v1\Kpi\ProbationEvaluationController::class, 'exportSalaryPdf']);
                    });
                });
                
                // ===== GENERIC IMPORT =====
                Route::post('/{module}/import', [\App\Http\Controllers\Api\v1\ImportController::class, 'import'])->name('api.generic.import');
            });
    });
});

import re

with open('routes/api.php', 'r') as f:
    content = f.read()

routes = """
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
"""

# Insert inside Route::middleware('ability:employee')->group(function () {
content = re.sub(
    r"(Route::middleware\('ability:employee'\)->group\(function \(\) \{)",
    r"\1\n" + routes,
    content
)

with open('routes/api.php', 'w') as f:
    f.write(content)

print("Routes injected")

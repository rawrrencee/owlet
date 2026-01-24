<?php

use App\Http\Controllers\EmployeeCompanyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by RouteServiceProvider and are assigned the "api"
| middleware group. All routes are prefixed with /api/v1.
|
*/

Route::prefix('v1')->group(function () {
    // Protected routes requiring authentication
    Route::middleware('auth:sanctum')->group(function () {
        // Employees
        Route::get('/employees', [UserController::class, 'index'])
            ->defaults('type', 'employees')
            ->name('api.employees.index');
        Route::post('/employees', [UserController::class, 'store'])
            ->name('api.employees.store');
        Route::get('/employees/{employee}', [UserController::class, 'show'])
            ->name('api.employees.show');
        Route::put('/employees/{employee}', [UserController::class, 'update'])
            ->name('api.employees.update');
        Route::patch('/employees/{employee}', [UserController::class, 'update'])
            ->name('api.employees.patch');
        Route::delete('/employees/{employee}', [UserController::class, 'destroy'])
            ->name('api.employees.destroy');

        // Customers
        Route::get('/customers', [UserController::class, 'index'])
            ->defaults('type', 'customers')
            ->name('api.customers.index');
        Route::post('/customers', [UserController::class, 'storeCustomer'])
            ->name('api.customers.store');
        Route::get('/customers/{customer}', [UserController::class, 'showCustomer'])
            ->name('api.customers.show');
        Route::put('/customers/{customer}', [UserController::class, 'updateCustomer'])
            ->name('api.customers.update');
        Route::patch('/customers/{customer}', [UserController::class, 'updateCustomer'])
            ->name('api.customers.patch');
        Route::delete('/customers/{customer}', [UserController::class, 'destroyCustomer'])
            ->name('api.customers.destroy');

        // Authenticated user info
        Route::get('/user', function () {
            return response()->json([
                'data' => new \App\Http\Resources\UserResource(auth()->user()),
            ]);
        })->name('api.user');

        // Companies - Uncomment when Sanctum is configured
        // Route::apiResource('companies', CompanyController::class);

        // Designations - Uncomment when Sanctum is configured
        // Route::apiResource('designations', DesignationController::class);

        // Employee-Company assignments
        Route::get('/employees/{employee}/companies', [EmployeeCompanyController::class, 'index'])
            ->name('api.employees.companies.index');
        Route::post('/employees/{employee}/companies', [EmployeeCompanyController::class, 'store'])
            ->name('api.employees.companies.store');
        Route::get('/employee-companies/{employeeCompany}', [EmployeeCompanyController::class, 'show'])
            ->name('api.employee-companies.show');
        Route::put('/employees/{employee}/companies/{employeeCompany}', [EmployeeCompanyController::class, 'update'])
            ->name('api.employees.companies.update');
        Route::delete('/employees/{employee}/companies/{employeeCompany}', [EmployeeCompanyController::class, 'destroy'])
            ->name('api.employees.companies.destroy');
    });
});

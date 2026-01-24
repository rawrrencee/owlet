<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmployeeCompanyController;
use App\Http\Controllers\EmployeeContractController;
use App\Http\Controllers\EmployeeInsuranceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::get('/', fn () => Inertia::render('Welcome'));

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Profile picture serving route (needs auth but not admin)
    Route::get('users/{employee}/profile-picture', [UserController::class, 'showProfilePicture'])->name('users.profile-picture');

    // Contract document viewing route (needs auth but not admin)
    Route::get('users/{employee}/contracts/{contract}/document', [EmployeeContractController::class, 'showDocument'])->name('users.contracts.document');

    // Insurance document viewing route (needs auth but not admin)
    Route::get('users/{employee}/insurances/{insurance}/document', [EmployeeInsuranceController::class, 'showDocument'])->name('users.insurances.document');

    Route::middleware('admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{employee}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{employee}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{employee}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{employee}/profile-picture', [UserController::class, 'uploadProfilePicture'])->name('users.upload-profile-picture');
        Route::delete('users/{employee}/profile-picture', [UserController::class, 'deleteProfilePicture'])->name('users.delete-profile-picture');
        Route::delete('users/{employee}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('customers/create', [UserController::class, 'createCustomer'])->name('customers.create');
        Route::post('customers', [UserController::class, 'storeCustomer'])->name('customers.store');
        Route::get('customers/{customer}', [UserController::class, 'showCustomer'])->name('customers.show');
        Route::get('customers/{customer}/edit', [UserController::class, 'editCustomer'])->name('customers.edit');
        Route::put('customers/{customer}', [UserController::class, 'updateCustomer'])->name('customers.update');
        Route::delete('customers/{customer}', [UserController::class, 'destroyCustomer'])->name('customers.destroy');

        // Companies
        Route::resource('companies', CompanyController::class)->except(['show']);
        Route::get('companies/{company}/logo', [CompanyController::class, 'showLogo'])->name('companies.logo');
        Route::post('companies/{company}/logo', [CompanyController::class, 'uploadLogo'])->name('companies.upload-logo');
        Route::delete('companies/{company}/logo', [CompanyController::class, 'deleteLogo'])->name('companies.delete-logo');

        // Designations
        Route::resource('designations', DesignationController::class)->except(['show']);

        // Employee-Company assignments
        Route::get('users/{employee}/companies', [EmployeeCompanyController::class, 'index'])->name('users.companies.index');
        Route::post('users/{employee}/companies', [EmployeeCompanyController::class, 'store'])->name('users.companies.store');
        Route::put('users/{employee}/companies/{employeeCompany}', [EmployeeCompanyController::class, 'update'])->name('users.companies.update');
        Route::delete('users/{employee}/companies/{employeeCompany}', [EmployeeCompanyController::class, 'destroy'])->name('users.companies.destroy');

        // Employee Contracts
        Route::get('users/{employee}/contracts', [EmployeeContractController::class, 'index'])->name('users.contracts.index');
        Route::post('users/{employee}/contracts', [EmployeeContractController::class, 'store'])->name('users.contracts.store');
        Route::put('users/{employee}/contracts/{contract}', [EmployeeContractController::class, 'update'])->name('users.contracts.update');
        Route::delete('users/{employee}/contracts/{contract}', [EmployeeContractController::class, 'destroy'])->name('users.contracts.destroy');
        Route::post('users/{employee}/contracts/{contract}/document', [EmployeeContractController::class, 'uploadDocument'])->name('users.contracts.upload-document');
        Route::delete('users/{employee}/contracts/{contract}/document', [EmployeeContractController::class, 'deleteDocument'])->name('users.contracts.delete-document');

        // Employee Insurances
        Route::get('users/{employee}/insurances', [EmployeeInsuranceController::class, 'index'])->name('users.insurances.index');
        Route::post('users/{employee}/insurances', [EmployeeInsuranceController::class, 'store'])->name('users.insurances.store');
        Route::put('users/{employee}/insurances/{insurance}', [EmployeeInsuranceController::class, 'update'])->name('users.insurances.update');
        Route::delete('users/{employee}/insurances/{insurance}', [EmployeeInsuranceController::class, 'destroy'])->name('users.insurances.destroy');
        Route::post('users/{employee}/insurances/{insurance}/document', [EmployeeInsuranceController::class, 'uploadDocument'])->name('users.insurances.upload-document');
        Route::delete('users/{employee}/insurances/{insurance}/document', [EmployeeInsuranceController::class, 'deleteDocument'])->name('users.insurances.delete-document');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

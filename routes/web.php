<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeCompanyController;
use App\Http\Controllers\EmployeeContractController;
use App\Http\Controllers\EmployeeInsuranceController;
use App\Http\Controllers\EmployeeStoreController;
use App\Http\Controllers\MyTeamController;
use App\Http\Controllers\OrganisationChartController;
use App\Http\Controllers\StoreController;
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

    // My Team - for employees with subordinates (staff or admin)
    Route::get('my-team', [MyTeamController::class, 'index'])->name('my-team.index');
    Route::get('my-team/{employee}', [MyTeamController::class, 'show'])->name('my-team.show');

    Route::middleware('admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{employee}', [UserController::class, 'show'])->name('users.show')->withTrashed();
        Route::get('users/{employee}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{employee}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{employee}/profile-picture', [UserController::class, 'uploadProfilePicture'])->name('users.upload-profile-picture');
        Route::delete('users/{employee}/profile-picture', [UserController::class, 'deleteProfilePicture'])->name('users.delete-profile-picture');
        Route::delete('users/{employee}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{employee}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
        Route::post('users/{employee}/resend-invitation', [UserController::class, 'resendInvitation'])->name('users.resend-invitation');

        Route::get('customers/create', [UserController::class, 'createCustomer'])->name('customers.create');
        Route::post('customers', [UserController::class, 'storeCustomer'])->name('customers.store');
        Route::get('customers/{customer}', [UserController::class, 'showCustomer'])->name('customers.show');
        Route::get('customers/{customer}/edit', [UserController::class, 'editCustomer'])->name('customers.edit');
        Route::put('customers/{customer}', [UserController::class, 'updateCustomer'])->name('customers.update');
        Route::delete('customers/{customer}', [UserController::class, 'destroyCustomer'])->name('customers.destroy');

        // Companies
        Route::resource('companies', CompanyController::class);
        Route::post('companies/{company}/restore', [CompanyController::class, 'restore'])->name('companies.restore')->withTrashed();
        Route::get('companies/{company}/logo', [CompanyController::class, 'showLogo'])->name('companies.logo');
        Route::post('companies/{company}/logo', [CompanyController::class, 'uploadLogo'])->name('companies.upload-logo');
        Route::delete('companies/{company}/logo', [CompanyController::class, 'deleteLogo'])->name('companies.delete-logo');

        // Company-Employee assignments (manage employees from company side)
        Route::get('companies/{company}/employees', [CompanyController::class, 'employees'])->name('companies.employees.index');
        Route::post('companies/{company}/employees', [CompanyController::class, 'addEmployee'])->name('companies.employees.store');
        Route::put('companies/{company}/employees/{employeeCompany}', [CompanyController::class, 'updateEmployee'])->name('companies.employees.update');
        Route::delete('companies/{company}/employees/{employeeCompany}', [CompanyController::class, 'removeEmployee'])->name('companies.employees.destroy');

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

        // Stores
        Route::resource('stores', StoreController::class);
        Route::post('stores/{store}/restore', [StoreController::class, 'restore'])->name('stores.restore')->withTrashed();
        Route::get('stores/{store}/logo', [StoreController::class, 'showLogo'])->name('stores.logo');
        Route::post('stores/{store}/logo', [StoreController::class, 'uploadLogo'])->name('stores.upload-logo');
        Route::delete('stores/{store}/logo', [StoreController::class, 'deleteLogo'])->name('stores.delete-logo');

        // Store-Employee assignments (manage employees from store side)
        Route::get('stores/{store}/employees', [StoreController::class, 'employees'])->name('stores.employees.index');
        Route::post('stores/{store}/employees', [StoreController::class, 'addEmployee'])->name('stores.employees.store');
        Route::put('stores/{store}/employees/{employeeStore}', [StoreController::class, 'updateEmployee'])->name('stores.employees.update');
        Route::delete('stores/{store}/employees/{employeeStore}', [StoreController::class, 'removeEmployee'])->name('stores.employees.destroy');

        // Employee-Store assignments
        Route::get('users/{employee}/stores', [EmployeeStoreController::class, 'index'])->name('users.stores.index');
        Route::post('users/{employee}/stores', [EmployeeStoreController::class, 'store'])->name('users.stores.store');
        Route::put('users/{employee}/stores/{employeeStore}', [EmployeeStoreController::class, 'update'])->name('users.stores.update');
        Route::delete('users/{employee}/stores/{employeeStore}', [EmployeeStoreController::class, 'destroy'])->name('users.stores.destroy');

        // Organisation Chart
        Route::get('organisation-chart', [OrganisationChartController::class, 'index'])->name('organisation-chart.index');

        // Employee Hierarchy (used in Edit User page)
        Route::get('users/{employee}/hierarchy', [OrganisationChartController::class, 'getEmployeeSubordinates'])->name('users.hierarchy.index');
        Route::post('users/{employee}/hierarchy', [OrganisationChartController::class, 'addSubordinate'])->name('users.hierarchy.store');
        Route::delete('users/{employee}/hierarchy/{subordinate}', [OrganisationChartController::class, 'removeSubordinate'])->name('users.hierarchy.destroy');
        Route::put('users/{employee}/hierarchy/visibility', [OrganisationChartController::class, 'updateVisibility'])->name('users.hierarchy.visibility');

        // Documents
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        // Create routes must be before parameter routes
        Route::get('documents/contracts/create', [DocumentController::class, 'createContract'])->name('documents.contracts.create');
        Route::post('documents/contracts', [DocumentController::class, 'storeContract'])->name('documents.contracts.store');
        Route::get('documents/insurances/create', [DocumentController::class, 'createInsurance'])->name('documents.insurances.create');
        Route::post('documents/insurances', [DocumentController::class, 'storeInsurance'])->name('documents.insurances.store');
        Route::get('documents/contracts/{contract}', [DocumentController::class, 'showContract'])->name('documents.contracts.show');
        Route::get('documents/contracts/{contract}/edit', [DocumentController::class, 'editContract'])->name('documents.contracts.edit');
        Route::put('documents/contracts/{contract}', [DocumentController::class, 'updateContract'])->name('documents.contracts.update');
        Route::get('documents/contracts/{contract}/document', [DocumentController::class, 'showContractDocument'])->name('documents.contracts.document');
        Route::post('documents/contracts/{contract}/document', [DocumentController::class, 'uploadContractDocument'])->name('documents.contracts.upload-document');
        Route::delete('documents/contracts/{contract}/document', [DocumentController::class, 'deleteContractDocument'])->name('documents.contracts.delete-document');
        Route::get('documents/insurances/{insurance}', [DocumentController::class, 'showInsurance'])->name('documents.insurances.show');
        Route::get('documents/insurances/{insurance}/edit', [DocumentController::class, 'editInsurance'])->name('documents.insurances.edit');
        Route::put('documents/insurances/{insurance}', [DocumentController::class, 'updateInsurance'])->name('documents.insurances.update');
        Route::get('documents/insurances/{insurance}/document', [DocumentController::class, 'showInsuranceDocument'])->name('documents.insurances.document');
        Route::post('documents/insurances/{insurance}/document', [DocumentController::class, 'uploadInsuranceDocument'])->name('documents.insurances.upload-document');
        Route::delete('documents/insurances/{insurance}/document', [DocumentController::class, 'deleteInsuranceDocument'])->name('documents.insurances.delete-document');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

<?php

use App\DataMigration\Http\Controllers\DataMigrationController;
use App\Http\Controllers\Admin\TimecardController as AdminTimecardController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeCompanyController;
use App\Http\Controllers\EmployeeContractController;
use App\Http\Controllers\EmployeeInsuranceController;
use App\Http\Controllers\EmployeePermissionController;
use App\Http\Controllers\EmployeeStoreController;
use App\Http\Controllers\MyTeamController;
use App\Http\Controllers\MyTeamTimecardController;
use App\Http\Controllers\OrganisationChartController;
use App\Http\Controllers\PagePermissionsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StocktakeController;
use App\Http\Controllers\StocktakeManagementController;
use App\Http\Controllers\StocktakeNotificationRecipientController;
use App\Http\Controllers\StocktakeTemplateController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TimecardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::get('/', fn () => Inertia::render('Welcome'));

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile picture serving route (needs auth but not admin)
    Route::get('users/{employee}/profile-picture', [UserController::class, 'showProfilePicture'])->name('users.profile-picture');

    // Contract document viewing route (needs auth but not admin)
    Route::get('users/{employee}/contracts/{contract}/document', [EmployeeContractController::class, 'showDocument'])->name('users.contracts.document');

    // Insurance document viewing route (needs auth but not admin)
    Route::get('users/{employee}/insurances/{insurance}/document', [EmployeeInsuranceController::class, 'showDocument'])->name('users.insurances.document');

    // My Team - for employees with subordinates (staff or admin)
    Route::get('my-team', [MyTeamController::class, 'index'])->name('my-team.index');
    Route::get('my-team/{employee}', [MyTeamController::class, 'show'])->name('my-team.show');

    // Employee Timecards (self-service)
    Route::get('timecards', [TimecardController::class, 'index'])->name('timecards.index');
    Route::get('timecards/current', [TimecardController::class, 'current'])->name('timecards.current');
    Route::post('timecards/clock-in', [TimecardController::class, 'clockIn'])->name('timecards.clock-in');
    Route::post('timecards/{timecard}/clock-out', [TimecardController::class, 'clockOut'])->name('timecards.clock-out');
    Route::post('timecards/{timecard}/start-break', [TimecardController::class, 'startBreak'])->name('timecards.start-break');
    Route::post('timecards/{timecard}/end-break', [TimecardController::class, 'endBreak'])->name('timecards.end-break');
    Route::post('timecards/{timecard}/resolve-incomplete', [TimecardController::class, 'resolveIncomplete'])->name('timecards.resolve-incomplete');
    Route::get('timecards/{date}', [TimecardController::class, 'show'])->name('timecards.show');

    // Team Timecards - for employees with subordinates
    Route::get('my-team-timecards', [MyTeamTimecardController::class, 'index'])->name('my-team-timecards.index');
    Route::get('my-team-timecards/{employee}', [MyTeamTimecardController::class, 'show'])->name('my-team-timecards.show');
    Route::get('my-team-timecards/{employee}/{date}', [MyTeamTimecardController::class, 'showDate'])->name('my-team-timecards.show-date');

    // Commerce routes - Brands (permission-based)
    // Create route must be defined before parameterized routes
    Route::middleware('permission:brands.manage')->group(function () {
        Route::get('brands/create', [BrandController::class, 'create'])->name('brands.create');
        Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
    });
    Route::middleware('permission:brands.view')->group(function () {
        Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
        Route::get('brands/{brand}/logo', [BrandController::class, 'showLogo'])->name('brands.logo');
    });
    Route::middleware('permission:brands.manage')->group(function () {
        Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
        Route::put('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
        Route::post('brands/{brand}/restore', [BrandController::class, 'restore'])->name('brands.restore')->withTrashed();
        Route::post('brands/{brand}/logo', [BrandController::class, 'uploadLogo'])->name('brands.upload-logo');
        Route::delete('brands/{brand}/logo', [BrandController::class, 'deleteLogo'])->name('brands.delete-logo');
    });

    // Commerce routes - Categories (permission-based)
    // Create route must be defined before parameterized routes
    Route::middleware('permission:categories.manage')->group(function () {
        Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    });
    Route::middleware('permission:categories.view')->group(function () {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    });
    Route::middleware('permission:categories.manage')->group(function () {
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore')->withTrashed();
        Route::post('categories/{category}/subcategories', [CategoryController::class, 'storeSubcategory'])->name('categories.subcategories.store');
        Route::put('categories/{category}/subcategories/{subcategory}', [CategoryController::class, 'updateSubcategory'])->name('categories.subcategories.update');
        Route::delete('categories/{category}/subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory'])->name('categories.subcategories.destroy');
        Route::post('categories/{category}/subcategories/{subcategory}/restore', [CategoryController::class, 'restoreSubcategory'])->name('categories.subcategories.restore')->withTrashed();
    });

    // Commerce routes - Suppliers (permission-based)
    // Create route must be defined before parameterized routes
    Route::middleware('permission:suppliers.manage')->group(function () {
        Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    });
    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::get('suppliers/{supplier}/logo', [SupplierController::class, 'showLogo'])->name('suppliers.logo');
    });
    Route::middleware('permission:suppliers.manage')->group(function () {
        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::post('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore')->withTrashed();
        Route::post('suppliers/{supplier}/logo', [SupplierController::class, 'uploadLogo'])->name('suppliers.upload-logo');
        Route::delete('suppliers/{supplier}/logo', [SupplierController::class, 'deleteLogo'])->name('suppliers.delete-logo');
    });

    // Commerce routes - Products (permission-based)
    // Create route must be defined before parameterized routes
    Route::middleware('permission:products.create')->group(function () {
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
        Route::get('products/{product}/create-variant', [ProductController::class, 'createVariant'])->name('products.create-variant');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
    });
    Route::middleware('permission:products.view')->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/ids', [ProductController::class, 'getAllIds'])->name('products.ids');
        Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('products/{product}/image', [ProductController::class, 'showImage'])->name('products.image');
        Route::get('products/{product}/images/{image}', [ProductController::class, 'showSupplementaryImage'])->name('products.supplementary-image');
        Route::get('products/{product}/variants', [ProductController::class, 'variants'])->name('products.variants');
        Route::get('products/{product}/search-linkable', [ProductController::class, 'searchLinkable'])->name('products.search-linkable');
        Route::get('products/{product}/preview', [ProductController::class, 'preview'])->name('products.preview');
        Route::get('products/{product}/adjacent', [ProductController::class, 'adjacentIds'])->name('products.adjacent');
    });
    Route::middleware('permission:products.edit')->group(function () {
        Route::post('products/batch-update', [ProductController::class, 'batchUpdate'])->name('products.batch-update');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('products/{product}/image', [ProductController::class, 'uploadImage'])->name('products.upload-image');
        Route::delete('products/{product}/image', [ProductController::class, 'deleteImage'])->name('products.delete-image');
        // Supplementary images
        Route::post('products/{product}/images', [ProductController::class, 'uploadSupplementaryImage'])->name('products.upload-supplementary-image');
        Route::delete('products/{product}/images/{image}', [ProductController::class, 'deleteSupplementaryImage'])->name('products.delete-supplementary-image');
        Route::post('products/{product}/images/reorder', [ProductController::class, 'reorderImages'])->name('products.reorder-images');
        Route::post('products/{product}/images/{image}/promote', [ProductController::class, 'promoteToCover'])->name('products.promote-to-cover');
        // Link existing product as variant
        Route::post('products/{product}/link-variant', [ProductController::class, 'linkAsVariant'])->name('products.link-variant');
    });
    Route::middleware('permission:products.delete')->group(function () {
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore')->withTrashed();
    });

    // Commerce routes - Stores (permission-based with policy authorization)
    // Manage store actions (must be defined before parameterized routes)
    Route::middleware('permission:stores.manage')->group(function () {
        Route::get('stores/create', [StoreController::class, 'create'])->name('stores.create');
        Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
        Route::delete('stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
        Route::post('stores/{store}/restore', [StoreController::class, 'restore'])->name('stores.restore')->withTrashed();
    });
    Route::middleware('permission:stores.access')->group(function () {
        Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('stores/{store}', [StoreController::class, 'show'])->name('stores.show');
        Route::get('stores/{store}/logo', [StoreController::class, 'showLogo'])->name('stores.logo');
        Route::get('stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::post('stores/{store}/logo', [StoreController::class, 'uploadLogo'])->name('stores.upload-logo');
        Route::delete('stores/{store}/logo', [StoreController::class, 'deleteLogo'])->name('stores.delete-logo');
        // Store-Employee assignments (manage employees from store side)
        Route::get('stores/{store}/employees', [StoreController::class, 'employees'])->name('stores.employees.index');
        Route::post('stores/{store}/employees', [StoreController::class, 'addEmployee'])->name('stores.employees.store');
        Route::put('stores/{store}/employees/{employeeStore}', [StoreController::class, 'updateEmployee'])->name('stores.employees.update');
        Route::delete('stores/{store}/employees/{employeeStore}', [StoreController::class, 'removeEmployee'])->name('stores.employees.destroy');
        // Store-Currency assignments (manage currencies from store side)
        Route::get('stores/{store}/currencies', [StoreController::class, 'currencies'])->name('stores.currencies.index');
        Route::post('stores/{store}/currencies', [StoreController::class, 'addCurrency'])->name('stores.currencies.store');
        Route::delete('stores/{store}/currencies/{storeCurrency}', [StoreController::class, 'removeCurrency'])->name('stores.currencies.destroy');
    });

    // Stocktake current state (used by floating widget, permission checked internally)
    Route::get('stocktakes/current', [StocktakeController::class, 'current'])->name('stocktakes.current');

    // Staff stocktake routes
    Route::middleware('permission:stocktakes.submit')->group(function () {
        Route::get('stocktakes', [StocktakeController::class, 'index'])->name('stocktakes.index');
        Route::post('stocktakes', [StocktakeController::class, 'store'])->name('stocktakes.store');
        Route::get('stocktakes/{stocktake}', [StocktakeController::class, 'show'])->name('stocktakes.show');
        Route::post('stocktakes/{stocktake}/items', [StocktakeController::class, 'addItem'])->name('stocktakes.items.store');
        Route::put('stocktakes/{stocktake}/items/{item}', [StocktakeController::class, 'updateItem'])->name('stocktakes.items.update');
        Route::delete('stocktakes/{stocktake}/items/{item}', [StocktakeController::class, 'removeItem'])->name('stocktakes.items.destroy');
        Route::post('stocktakes/{stocktake}/submit', [StocktakeController::class, 'submit'])->name('stocktakes.submit');
        Route::delete('stocktakes/{stocktake}', [StocktakeController::class, 'destroy'])->name('stocktakes.destroy');
        Route::get('stocktakes/{stocktake}/search-products', [StocktakeController::class, 'searchProducts'])->name('stocktakes.search-products');
        Route::post('stocktakes/{stocktake}/apply-template', [StocktakeController::class, 'applyTemplate'])->name('stocktakes.apply-template');

        // Staff templates
        Route::get('stocktake-templates/search-products', [StocktakeTemplateController::class, 'searchProducts'])->name('stocktake-templates.search-products');
        Route::get('stocktake-templates', [StocktakeTemplateController::class, 'index'])->name('stocktake-templates.index');
        Route::get('stocktake-templates/create', [StocktakeTemplateController::class, 'create'])->name('stocktake-templates.create');
        Route::post('stocktake-templates', [StocktakeTemplateController::class, 'store'])->name('stocktake-templates.store');
        Route::get('stocktake-templates/{stocktakeTemplate}/edit', [StocktakeTemplateController::class, 'edit'])->name('stocktake-templates.edit');
        Route::put('stocktake-templates/{stocktakeTemplate}', [StocktakeTemplateController::class, 'update'])->name('stocktake-templates.update');
        Route::delete('stocktake-templates/{stocktakeTemplate}', [StocktakeTemplateController::class, 'destroy'])->name('stocktake-templates.destroy');
    });

    // Stocktake management routes
    Route::middleware('permission:stocktakes.manage')->prefix('management')->name('management.')->group(function () {
        Route::get('stocktakes', [StocktakeManagementController::class, 'index'])->name('stocktakes.index');
        Route::get('stocktakes/{stocktake}', [StocktakeManagementController::class, 'show'])->name('stocktakes.show');
    });

    // Lost/Found adjustments
    Route::middleware('permission:stocktakes.lost_and_found')->prefix('management')->name('management.')->group(function () {
        Route::post('stocktakes/adjust-quantity', [StocktakeManagementController::class, 'adjustQuantity'])->name('stocktakes.adjust-quantity');
    });

    // Admin-only routes
    Route::middleware('admin')->group(function () {
        // Page Permissions Management
        Route::get('page-permissions/{page}', [PagePermissionsController::class, 'index'])->name('page-permissions.index');
        Route::put('page-permissions/{page}', [PagePermissionsController::class, 'update'])->name('page-permissions.update');

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

        // Employee Permissions
        Route::get('users/{employee}/permissions', [EmployeePermissionController::class, 'index'])->name('users.permissions.index');
        Route::put('users/{employee}/permissions', [EmployeePermissionController::class, 'update'])->name('users.permissions.update');

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

        // Currencies
        Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
        Route::get('currencies/create', [CurrencyController::class, 'create'])->name('currencies.create');
        Route::post('currencies', [CurrencyController::class, 'store'])->name('currencies.store');
        Route::post('currencies/refresh-rates', [CurrencyController::class, 'refreshRates'])->name('currencies.refresh-rates');
        Route::get('currencies/{currency}', [CurrencyController::class, 'show'])->name('currencies.show');
        Route::get('currencies/{currency}/edit', [CurrencyController::class, 'edit'])->name('currencies.edit');
        Route::put('currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
        Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');

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

        // Employee-Store assignments
        Route::get('users/{employee}/stores', [EmployeeStoreController::class, 'index'])->name('users.stores.index');
        Route::post('users/{employee}/stores', [EmployeeStoreController::class, 'store'])->name('users.stores.store');
        Route::put('users/{employee}/stores/{employeeStore}', [EmployeeStoreController::class, 'update'])->name('users.stores.update');
        Route::delete('users/{employee}/stores/{employeeStore}', [EmployeeStoreController::class, 'destroy'])->name('users.stores.destroy');

        // Organisation Chart
        Route::get('organisation-chart', [OrganisationChartController::class, 'index'])->name('organisation-chart.index');
        Route::get('organisation-chart/edit', [OrganisationChartController::class, 'edit'])->name('organisation-chart.edit');
        Route::get('organisation-chart/employees/{employee}/managers', [OrganisationChartController::class, 'getEmployeeManagers'])->name('organisation-chart.employee-managers');
        Route::post('organisation-chart/employees/{employee}/managers', [OrganisationChartController::class, 'addManager'])->name('organisation-chart.add-manager');
        Route::delete('organisation-chart/employees/{employee}/managers/{manager}', [OrganisationChartController::class, 'removeManager'])->name('organisation-chart.remove-manager');
        Route::post('organisation-chart/bulk-assign', [OrganisationChartController::class, 'bulkAssignManager'])->name('organisation-chart.bulk-assign');

        // Employee Hierarchy (used in Edit User page)
        Route::get('users/{employee}/hierarchy', [OrganisationChartController::class, 'getEmployeeSubordinates'])->name('users.hierarchy.index');
        Route::post('users/{employee}/hierarchy', [OrganisationChartController::class, 'addSubordinate'])->name('users.hierarchy.store');
        Route::delete('users/{employee}/hierarchy/{subordinate}', [OrganisationChartController::class, 'removeSubordinate'])->name('users.hierarchy.destroy');
        Route::put('users/{employee}/hierarchy/visibility', [OrganisationChartController::class, 'updateVisibility'])->name('users.hierarchy.visibility');

        // Management - Stocktake Templates & Notifications (admin only)
        Route::prefix('management')->name('management.')->group(function () {
            // Template management
            Route::get('stocktake-templates', [StocktakeTemplateController::class, 'adminIndex'])->name('stocktake-templates.index');
            Route::get('stocktake-templates/{template}/edit', [StocktakeTemplateController::class, 'adminEdit'])->name('stocktake-templates.edit');
            Route::put('stocktake-templates/{template}', [StocktakeTemplateController::class, 'adminUpdate'])->name('stocktake-templates.update');
            Route::delete('stocktake-templates/{template}', [StocktakeTemplateController::class, 'adminDestroy'])->name('stocktake-templates.destroy');

            // Notification recipient CRUD
            Route::get('notifications/stocktake', [StocktakeNotificationRecipientController::class, 'index'])->name('notifications.stocktake.index');
            Route::post('notifications/stocktake', [StocktakeNotificationRecipientController::class, 'store'])->name('notifications.stocktake.store');
            Route::put('notifications/stocktake/{recipient}', [StocktakeNotificationRecipientController::class, 'update'])->name('notifications.stocktake.update');
            Route::delete('notifications/stocktake/{recipient}', [StocktakeNotificationRecipientController::class, 'destroy'])->name('notifications.stocktake.destroy');
        });

        // Management - Timecards
        Route::prefix('management')->name('management.')->group(function () {
            Route::get('timecards', [AdminTimecardController::class, 'index'])->name('timecards.index');
            Route::get('timecards/create', [AdminTimecardController::class, 'create'])->name('timecards.create');
            Route::post('timecards', [AdminTimecardController::class, 'store'])->name('timecards.store');
            Route::get('timecards/date/{date}', [AdminTimecardController::class, 'byDate'])->name('timecards.by-date');
            Route::get('timecards/employee/{employee}', [AdminTimecardController::class, 'byEmployee'])->name('timecards.by-employee');
            Route::get('timecards/{timecard}', [AdminTimecardController::class, 'show'])->name('timecards.show');
            Route::get('timecards/{timecard}/edit', [AdminTimecardController::class, 'edit'])->name('timecards.edit');
            Route::put('timecards/{timecard}', [AdminTimecardController::class, 'update'])->name('timecards.update');
            Route::delete('timecards/{timecard}', [AdminTimecardController::class, 'destroy'])->name('timecards.destroy');
            // Timecard Details CRUD
            Route::post('timecards/{timecard}/details', [AdminTimecardController::class, 'storeDetail'])->name('timecards.details.store');
            Route::put('timecards/{timecard}/details/{detail}', [AdminTimecardController::class, 'updateDetail'])->name('timecards.details.update');
            Route::delete('timecards/{timecard}/details/{detail}', [AdminTimecardController::class, 'destroyDetail'])->name('timecards.details.destroy');
        });

        // Data Migration
        Route::prefix('admin/data-migration')->name('admin.data-migration.')->group(function () {
            Route::get('/', [DataMigrationController::class, 'index'])->name('index');
            Route::get('/{modelType}', [DataMigrationController::class, 'show'])->name('show');
            Route::post('/{modelType}/migrate', [DataMigrationController::class, 'migrate'])->name('migrate');
            Route::post('/{modelType}/verify', [DataMigrationController::class, 'verify'])->name('verify');
            Route::post('/{modelType}/retry-failed', [DataMigrationController::class, 'retryFailed'])->name('retry-failed');
            Route::post('/test-connection', [DataMigrationController::class, 'testConnection'])->name('test-connection');
        });

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

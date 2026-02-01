<?php

namespace App\Http\Controllers;

use App\Constants\StoreAccessPermissions;
use App\Constants\StorePermissions;
use App\Http\Requests\StoreEmployeeStoreRequest;
use App\Http\Requests\StoreStoreCurrencyRequest;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateEmployeeStoreRequest;
use App\Http\Requests\UpdateStoreCurrencyRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\CurrencyResource;
use App\Http\Resources\EmployeeStoreResource;
use App\Http\Resources\StoreCurrencyResource;
use App\Http\Resources\StoreResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\EmployeeStore;
use App\Models\Store;
use App\Models\StoreCurrency;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function __construct(
        protected PermissionService $permissionService
    ) {}

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = $request->user();

        // Filter stores based on user permissions
        $accessibleStoreIds = $this->permissionService->getAccessibleStoreIds($user);
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $companyId = $request->query('company_id', '');
        $showDeleted = $request->boolean('show_deleted', false);
        $perPage = min(max($request->integer('per_page', 15), 10), 100);

        $query = Store::with(['company', 'defaultStoreCurrency.currency', 'storeCurrencies']);

        // Filter to accessible stores for non-admin users
        if (! $user->isAdmin()) {
            $query->whereIn('id', $accessibleStoreIds);
        }

        if ($showDeleted) {
            $query->withTrashed();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('store_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $stores = $query
            ->orderBy('store_name')
            ->paginate($perPage)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return StoreResource::collection($stores)->response();
        }

        $transformedStores = [
            'data' => StoreResource::collection($stores->items())->resolve(),
            'current_page' => $stores->currentPage(),
            'last_page' => $stores->lastPage(),
            'per_page' => $stores->perPage(),
            'total' => $stores->total(),
        ];

        $companies = Company::where('active', true)
            ->orderBy('company_name')
            ->get();

        return Inertia::render('Stores/Index', [
            'stores' => $transformedStores,
            'companies' => CompanyResource::collection($companies)->resolve(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'company_id' => $companyId,
                'show_deleted' => $showDeleted,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $companies = Company::where('active', true)
            ->orderBy('company_name')
            ->get();

        return Inertia::render('Stores/Form', [
            'store' => null,
            'companies' => CompanyResource::collection($companies)->resolve(),
            'countries' => Country::active()->ordered()->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreStoreRequest $request): RedirectResponse|JsonResponse
    {
        $data = collect($request->validated())->except('logo')->toArray();
        $store = Store::create($data);

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store(
                "store-logos/{$store->id}",
                'private'
            );
            $store->update(['logo' => $path]);
        }

        // Auto-assign the creator (staff user) to the store so it's visible to them
        // Grant them all access permissions since they created the store
        $user = $request->user();
        if ($user->isStaff() && $user->employee_id) {
            EmployeeStore::create([
                'employee_id' => $user->employee_id,
                'store_id' => $store->id,
                'active' => true,
                'permissions' => [],
                'access_permissions' => StoreAccessPermissions::keys(),
                'is_creator' => true,
            ]);
        }

        return $this->respondWithCreated(
            $request,
            'stores.index',
            [],
            'Store created successfully.',
            new StoreResource($store->fresh('company'))
        );
    }

    public function show(Request $request, Store $store): InertiaResponse|JsonResponse
    {
        $this->authorize('view', $store);

        $store->load([
            'company',
            'country',
            'employeeStores.employee.user',
            'storeCurrencies.currency',
            'defaultStoreCurrency.currency',
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new StoreResource($store))->resolve(),
            ]);
        }

        return Inertia::render('Stores/View', [
            'store' => (new StoreResource($store))->resolve(),
            'employeeStores' => $store->employeeStores->map(fn ($es) => [
                'id' => $es->id,
                'employee_id' => $es->employee_id,
                'employee_name' => $es->employee->full_name,
                'employee_number' => $es->employee->employee_number,
                'profile_picture_url' => $es->employee->getProfilePictureUrl(),
                'active' => $es->active,
                'permissions' => $es->permissions ?? [],
                'permissions_with_labels' => $es->getPermissionsWithLabels(),
                'is_creator' => $es->is_creator,
            ]),
            'storeCurrencies' => StoreCurrencyResource::collection($store->storeCurrencies)->resolve(),
        ]);
    }

    public function edit(Store $store): InertiaResponse
    {
        $this->authorize('update', $store);

        $store->load(['company', 'country', 'storeCurrencies.currency', 'defaultStoreCurrency.currency']);

        $companies = Company::where('active', true)
            ->orderBy('company_name')
            ->get();

        // Get active employees for assignment
        $employees = Employee::whereNull('termination_date')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'employee_number']);

        // Get all active currencies
        $currencies = Currency::active()
            ->orderBy('code')
            ->get();

        return Inertia::render('Stores/Form', [
            'store' => (new StoreResource($store))->resolve(),
            'companies' => CompanyResource::collection($companies)->resolve(),
            'employees' => $employees,
            'currencies' => CurrencyResource::collection($currencies)->resolve(),
            'countries' => Country::active()->ordered()->get(['id', 'name', 'code']),
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $store);

        $store->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'stores.show',
            ['store' => $store->id],
            'Store updated successfully.',
            (new StoreResource($store->fresh('company')))->resolve()
        );
    }

    public function destroy(Request $request, Store $store): RedirectResponse|JsonResponse
    {
        // Delete logo if exists
        if ($store->logo) {
            Storage::disk('private')->delete($store->logo);
        }

        $store->delete();

        return $this->respondWithDeleted(
            $request,
            'stores.index',
            [],
            'Store deleted successfully.'
        );
    }

    public function restore(Request $request, Store $store): RedirectResponse|JsonResponse
    {
        $store->restore();

        return $this->respondWithSuccess(
            $request,
            'stores.index',
            ['show_deleted' => true],
            'Store restored successfully.',
            (new StoreResource($store->fresh('company')))->resolve()
        );
    }

    public function showLogo(Store $store): StreamedResponse
    {
        if (! $store->logo || ! Storage::disk('private')->exists($store->logo)) {
            abort(404);
        }

        return Storage::disk('private')->response($store->logo);
    }

    public function uploadLogo(Store $store, Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Delete old logo if exists
        if ($store->logo) {
            Storage::disk('private')->delete($store->logo);
        }

        // Store new logo
        $path = $request->file('logo')->store(
            "store-logos/{$store->id}",
            'private'
        );

        $store->update(['logo' => $path]);

        return $this->respondWithSuccess(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Logo uploaded successfully.',
            ['logo_url' => route('stores.logo', $store->id)]
        );
    }

    public function deleteLogo(Store $store, Request $request): RedirectResponse|JsonResponse
    {
        if ($store->logo) {
            Storage::disk('private')->delete($store->logo);
            $store->update(['logo' => null]);
        }

        return $this->respondWithSuccess(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Logo removed successfully.',
            ['logo_url' => null]
        );
    }

    /**
     * Get all employees assigned to a store (for AJAX/API).
     */
    public function employees(Request $request, Store $store): JsonResponse
    {
        $this->authorize('manageEmployees', $store);

        $employeeStores = $store->employeeStores()
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => EmployeeStoreResource::collection($employeeStores),
            'available_permissions' => StorePermissions::grouped(),
            'available_access_permissions' => StoreAccessPermissions::grouped(),
        ]);
    }

    /**
     * Add an employee to a store.
     */
    public function addEmployee(StoreEmployeeStoreRequest $request, Store $store): RedirectResponse|JsonResponse
    {
        $this->authorize('manageEmployees', $store);

        $data = $request->validated();
        $data['store_id'] = $store->id;

        $employeeStore = EmployeeStore::create($data);
        $employeeStore->load('employee');

        return $this->respondWithCreated(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Employee added successfully.',
            new EmployeeStoreResource($employeeStore)
        );
    }

    /**
     * Update an employee assignment for a store.
     */
    public function updateEmployee(UpdateEmployeeStoreRequest $request, Store $store, EmployeeStore $employeeStore): RedirectResponse|JsonResponse
    {
        $this->authorize('manageEmployees', $store);

        $employeeStore->update($request->validated());
        $employeeStore->load('employee');

        return $this->respondWithSuccess(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Employee assignment updated successfully.',
            (new EmployeeStoreResource($employeeStore->fresh('employee')))->resolve()
        );
    }

    /**
     * Remove an employee from a store.
     */
    public function removeEmployee(Request $request, Store $store, EmployeeStore $employeeStore): RedirectResponse|JsonResponse
    {
        $this->authorize('manageEmployees', $store);

        // Prevent removal of the store creator assignment
        if ($employeeStore->is_creator) {
            return back()->withErrors([
                'employee' => 'Cannot remove the store creator from this store.',
            ]);
        }

        $employeeStore->delete();

        return $this->respondWithDeleted(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Employee removed successfully.'
        );
    }

    /**
     * Get all currencies assigned to a store (for AJAX/API).
     */
    public function currencies(Request $request, Store $store): JsonResponse
    {
        $this->authorize('manageCurrencies', $store);

        $storeCurrencies = $store->storeCurrencies()
            ->with('currency')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => StoreCurrencyResource::collection($storeCurrencies),
        ]);
    }

    /**
     * Add a currency to a store.
     */
    public function addCurrency(StoreStoreCurrencyRequest $request, Store $store): RedirectResponse|JsonResponse
    {
        $this->authorize('manageCurrencies', $store);

        $data = $request->validated();
        $data['store_id'] = $store->id;

        // If this is the first currency, make it default
        $isFirst = ! $store->storeCurrencies()->exists();
        $data['is_default'] = $isFirst;

        $storeCurrency = StoreCurrency::create($data);
        $storeCurrency->load('currency');

        return $this->respondWithCreated(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Currency added successfully.',
            new StoreCurrencyResource($storeCurrency)
        );
    }

    /**
     * Update a currency assignment for a store.
     */
    public function updateCurrency(UpdateStoreCurrencyRequest $request, Store $store, StoreCurrency $storeCurrency): RedirectResponse|JsonResponse
    {
        $this->authorize('manageCurrencies', $store);

        $storeCurrency->update($request->validated());
        $storeCurrency->load('currency');

        return $this->respondWithSuccess(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Currency assignment updated successfully.',
            (new StoreCurrencyResource($storeCurrency->fresh('currency')))->resolve()
        );
    }

    /**
     * Remove a currency from a store.
     */
    public function removeCurrency(Request $request, Store $store, StoreCurrency $storeCurrency): RedirectResponse|JsonResponse
    {
        $this->authorize('manageCurrencies', $store);

        $wasDefault = $storeCurrency->is_default;
        $storeCurrency->delete();

        // If removed currency was default, make another one default (if any)
        if ($wasDefault) {
            $firstRemaining = $store->storeCurrencies()->first();
            if ($firstRemaining) {
                $firstRemaining->update(['is_default' => true]);
            }
        }

        return $this->respondWithDeleted(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Currency removed successfully.'
        );
    }

    /**
     * Set a currency as the default for a store.
     */
    public function setDefaultCurrency(Request $request, Store $store, StoreCurrency $storeCurrency): RedirectResponse|JsonResponse
    {
        $this->authorize('manageCurrencies', $store);

        // Remove default from all other currencies
        $store->storeCurrencies()
            ->where('id', '!=', $storeCurrency->id)
            ->update(['is_default' => false]);

        // Set this currency as default
        $storeCurrency->update(['is_default' => true]);

        return $this->respondWithSuccess(
            $request,
            'stores.edit',
            ['store' => $store->id],
            'Default currency updated successfully.',
            (new StoreCurrencyResource($storeCurrency->fresh('currency')))->resolve()
        );
    }
}

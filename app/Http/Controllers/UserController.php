<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkOSException;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\DesignationResource;
use App\Http\Resources\EmployeeCompanyResource;
use App\Http\Resources\EmployeeContractResource;
use App\Http\Resources\EmployeeInsuranceResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeStoreResource;
use App\Http\Resources\StoreResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Company;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Store;
use App\Services\WorkOSUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class UserController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $type = $request->query('type', 'employees');
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $companyId = $request->query('company', '');
        $showDeleted = $request->boolean('show_deleted');
        $perPage = min(max($request->integer('per_page', 15), 10), 100);

        if ($type === 'customers') {
            $query = Customer::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            }

            $users = $query
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate($perPage)
                ->withQueryString();

            if ($this->wantsJson($request)) {
                return CustomerResource::collection($users)->response();
            }
        } else {
            $query = Employee::query()->with(['user', 'employeeCompanies.company']);

            if ($showDeleted) {
                $query->withTrashed();
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('email', 'like', "%{$search}%");
                        });
                });
            }

            if ($status === 'active') {
                $query->whereNull('termination_date');
            } elseif ($status === 'terminated') {
                $query->whereNotNull('termination_date');
            }

            if ($companyId) {
                $query->whereHas('employeeCompanies', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                        ->whereNull('left_date');
                });
            }

            $users = $query
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate($perPage)
                ->withQueryString();

            if ($this->wantsJson($request)) {
                return EmployeeResource::collection($users)->response();
            }
        }

        // Transform through resources for Inertia with flattened pagination
        $transformedUsers = [
            'data' => $type === 'customers'
                ? CustomerResource::collection($users->items())->resolve()
                : EmployeeResource::collection($users->items())->resolve(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ];

        // Get companies for filter dropdown
        $companies = Company::where('active', true)
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        return Inertia::render('Users/Index', [
            'users' => $transformedUsers,
            'type' => $type,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'company' => $companyId,
                'show_deleted' => $showDeleted,
                'per_page' => $perPage,
            ],
            'companies' => $companies,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Users/Form', [
            'employee' => null,
            'workosUser' => null,
            'countries' => Country::active()->ordered()->get(['id', 'name', 'code', 'nationality_name']),
        ]);
    }

    public function show(Request $request, Employee $employee, WorkOSUserService $workOSUserService): InertiaResponse|JsonResponse
    {
        $employee->load([
            'user',
            'employeeCompanies.company',
            'employeeCompanies.designation',
            'contracts.company',
            'insurances',
            'employeeStores.store',
            'countryOfResidence',
            'nationalityCountry',
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        $workosUser = null;
        $workosRole = null;

        if ($employee->user?->workos_id) {
            try {
                $workosUser = $workOSUserService->getUser($employee->user->workos_id);
                $workosRole = $workOSUserService->getUserRole($employee->user->workos_id);
            } catch (WorkOSException) {
                // WorkOS user not found, continue without it
            }
        }

        $validRoles = ['admin', 'staff'];
        $role = $workosRole ?? $employee->user?->role ?? 'staff';
        if (! in_array($role, $validRoles, true)) {
            $role = 'staff';
        }

        if ($this->wantsJson($request)) {
            $resource = new EmployeeResource($employee);

            return response()->json([
                'data' => $resource->resolve(),
                'workos_user' => $workosUser ? [
                    'id' => $workosUser->id,
                    'email' => $workosUser->email,
                    'first_name' => $workosUser->firstName,
                    'last_name' => $workosUser->lastName,
                    'email_verified' => $workosUser->emailVerified,
                    'profile_picture_url' => $workosUser->profilePictureUrl,
                    'created_at' => $workosUser->createdAt,
                    'updated_at' => $workosUser->updatedAt,
                ] : null,
                'role' => $role,
            ]);
        }

        return Inertia::render('Users/View', [
            'employee' => (new EmployeeResource($employee))->resolve(),
            'workosUser' => $workosUser ? [
                'id' => $workosUser->id,
                'email' => $workosUser->email,
                'firstName' => $workosUser->firstName,
                'lastName' => $workosUser->lastName,
                'emailVerified' => $workosUser->emailVerified,
                'profilePictureUrl' => $workosUser->profilePictureUrl,
                'createdAt' => $workosUser->createdAt,
                'updatedAt' => $workosUser->updatedAt,
            ] : null,
            'role' => $role,
            'employeeCompanies' => EmployeeCompanyResource::collection(
                $employee->employeeCompanies->sortByDesc('commencement_date')
            )->resolve(),
            'contracts' => EmployeeContractResource::collection(
                $employee->contracts->sortByDesc('start_date')
            )->resolve(),
            'insurances' => EmployeeInsuranceResource::collection(
                $employee->insurances->sortByDesc('start_date')
            )->resolve(),
            'stores' => EmployeeStoreResource::collection(
                $employee->employeeStores->sortByDesc('start_date')
            )->resolve(),
        ]);
    }

    public function edit(Employee $employee, WorkOSUserService $workOSUserService): InertiaResponse
    {
        $employee->load(['user', 'employeeCompanies.company', 'employeeCompanies.designation', 'contracts.company', 'insurances', 'countryOfResidence', 'nationalityCountry']);

        $workosUser = null;
        $workosRole = null;

        if ($employee->user?->workos_id) {
            try {
                $workosUser = $workOSUserService->getUser($employee->user->workos_id);
                $workosRole = $workOSUserService->getUserRole($employee->user->workos_id);
            } catch (WorkOSException) {
                // WorkOS user not found, continue without it
            }
        }

        $validRoles = ['admin', 'staff'];
        $role = $workosRole ?? $employee->user?->role ?? 'staff';
        if (! in_array($role, $validRoles, true)) {
            $role = 'staff';
        }

        return Inertia::render('Users/Form', [
            'employee' => (new EmployeeResource($employee))->resolve(),
            'workosUser' => $workosUser ? [
                'id' => $workosUser->id,
                'email' => $workosUser->email,
                'firstName' => $workosUser->firstName,
                'lastName' => $workosUser->lastName,
                'emailVerified' => $workosUser->emailVerified,
                'profilePictureUrl' => $workosUser->profilePictureUrl,
                'createdAt' => $workosUser->createdAt,
                'updatedAt' => $workosUser->updatedAt,
            ] : null,
            'role' => $role,
            'employeeCompanies' => EmployeeCompanyResource::collection(
                $employee->employeeCompanies->sortByDesc('commencement_date')
            )->resolve(),
            'contracts' => EmployeeContractResource::collection(
                $employee->contracts->sortByDesc('start_date')
            )->resolve(),
            'insurances' => EmployeeInsuranceResource::collection(
                $employee->insurances->sortByDesc('start_date')
            )->resolve(),
            'companies' => CompanyResource::collection(Company::where('active', true)->orderBy('company_name')->get())->resolve(),
            'designations' => DesignationResource::collection(Designation::orderBy('designation_name')->get())->resolve(),
            'stores' => StoreResource::collection(Store::where('active', true)->orderBy('store_name')->get())->resolve(),
            'countries' => Country::active()->ordered()->get(['id', 'name', 'code', 'nationality_name']),
        ]);
    }

    public function update(Employee $employee, Request $request, WorkOSUserService $workOSUserService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'chinese_name' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'nric' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:Male,Female'],
            'race' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'nationality_id' => ['nullable', 'exists:countries,id'],
            'residency_status' => ['nullable', 'string', 'max:50'],
            'pr_conversion_date' => ['nullable', 'date'],
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact' => ['nullable', 'string', 'max:50'],
            'emergency_address_1' => ['nullable', 'string', 'max:255'],
            'emergency_address_2' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'hire_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'role' => ['required', 'string', 'in:admin,staff'],
        ]);

        $employee->load('user.employee');

        $employeeData = collect($validated)->except('role')->toArray();
        $employee->update($employeeData);

        if ($employee->user?->workos_id) {
            try {
                $employee->user->setRelation('employee', $employee);
                $workOSUserService->updateEmployee($employee->user, $validated);

                return $this->respondWithSuccess(
                    $request,
                    'users.index',
                    [],
                    'User updated successfully.',
                    (new EmployeeResource($employee->fresh('user')))->resolve()
                );
            } catch (WorkOSException $e) {
                return $this->respondWithError($request, $e->message, $e->toArray(), 422);
            }
        }

        return $this->respondWithSuccess(
            $request,
            'users.index',
            [],
            'Employee updated successfully.',
            (new EmployeeResource($employee->fresh('user')))->resolve()
        );
    }

    public function storeCustomer(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($validated['email'])) {
            Customer::withTrashed()
                ->where('email', $validated['email'])
                ->whereNotNull('deleted_at')
                ->forceDelete();
        }

        $validated['customer_since'] = now();

        $customer = Customer::create($validated);

        return $this->respondWithCreated(
            $request,
            'users.index',
            ['type' => 'customers'],
            'Customer created successfully.',
            new CustomerResource($customer)
        );
    }

    public function showCustomer(Request $request, Customer $customer): InertiaResponse|JsonResponse
    {
        $customer->load([
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new CustomerResource($customer))->resolve(),
            ]);
        }

        return Inertia::render('Customers/View', [
            'customer' => (new CustomerResource($customer))->resolve(),
        ]);
    }

    public function editCustomer(Customer $customer): InertiaResponse
    {
        return Inertia::render('Customers/Form', [
            'customer' => $customer,
        ]);
    }

    public function updateCustomer(Customer $customer, Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->update($validated);

        return $this->respondWithSuccess(
            $request,
            'users.index',
            ['type' => 'customers'],
            'Customer updated successfully.',
            (new CustomerResource($customer->fresh()))->resolve()
        );
    }

    public function createCustomer(): InertiaResponse
    {
        return Inertia::render('Customers/Form', [
            'customer' => null,
        ]);
    }

    public function destroy(Request $request, Employee $employee, WorkOSUserService $workOSUserService): RedirectResponse|JsonResponse
    {
        $employee->load('user');

        if ($employee->user?->workos_id) {
            try {
                $workOSUserService->deleteUser($employee->user->workos_id);
            } catch (WorkOSException $e) {
                if ($e->errorCode !== 'entity_not_found') {
                    return $this->respondWithError($request, $e->message, $e->toArray(), 422);
                }
            }
        }

        if ($employee->user) {
            $employee->user->delete();
        }

        $employee->delete();

        return $this->respondWithDeleted(
            $request,
            'users.index',
            [],
            'User deleted successfully.'
        );
    }

    public function restore(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $employee->restore();

        if ($employee->user()->withTrashed()->exists()) {
            $employee->user()->withTrashed()->first()->restore();
        }

        return $this->respondWithSuccess(
            $request,
            'users.index',
            ['show_deleted' => true],
            'User restored successfully.',
            (new EmployeeResource($employee->fresh('user')))->resolve()
        );
    }

    public function destroyCustomer(Request $request, Customer $customer): RedirectResponse|JsonResponse
    {
        $customer->delete();

        return $this->respondWithDeleted(
            $request,
            'users.index',
            ['type' => 'customers'],
            'Customer deleted successfully.'
        );
    }

    public function showProfilePicture(Employee $employee): StreamedResponse
    {
        if (! $employee->profile_picture || ! Storage::disk('private')->exists($employee->profile_picture)) {
            abort(404);
        }

        return Storage::disk('private')->response($employee->profile_picture);
    }

    public function uploadProfilePicture(Employee $employee, Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'profile_picture' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Delete old profile picture if exists
        if ($employee->profile_picture) {
            Storage::disk('private')->delete($employee->profile_picture);
        }

        // Store new profile picture
        $path = $request->file('profile_picture')->store(
            "profile-pictures/{$employee->id}",
            'private'
        );

        $employee->update(['profile_picture' => $path]);

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Profile picture uploaded successfully.',
            ['profile_picture_url' => route('users.profile-picture', $employee->id)]
        );
    }

    public function deleteProfilePicture(Employee $employee, Request $request): RedirectResponse|JsonResponse
    {
        if ($employee->profile_picture) {
            Storage::disk('private')->delete($employee->profile_picture);
            $employee->update(['profile_picture' => null]);
        }

        // Refresh the employee to get the updated profile_picture_url with fallback to external_avatar_url
        $employee->refresh();

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Profile picture removed successfully.',
            ['profile_picture_url' => $employee->external_avatar_url]
        );
    }

    public function resendInvitation(Employee $employee, Request $request, WorkOSUserService $workOSUserService): RedirectResponse|JsonResponse
    {
        $employee->load('user');

        // Check if employee already has a WorkOS account linked
        if ($employee->user?->workos_id) {
            return $this->respondWithError(
                $request,
                'This user already has an active account and does not need an invitation.',
                ['code' => 'already_active'],
                422
            );
        }

        // Check if there's a pending email
        if (! $employee->pending_email) {
            return $this->respondWithError(
                $request,
                'This employee does not have a pending invitation.',
                ['code' => 'no_pending_invitation'],
                422
            );
        }

        try {
            $workOSUserService->sendInvitation($employee->pending_email, $employee->pending_role ?? 'staff');

            return $this->respondWithSuccess(
                $request,
                'users.show',
                ['employee' => $employee->id],
                'Invitation email has been resent successfully.',
                ['email' => $employee->pending_email]
            );
        } catch (WorkOSException $e) {
            return $this->respondWithError($request, $e->message, $e->toArray(), 422);
        }
    }

    public function store(StoreUserRequest $request, WorkOSUserService $workOSUserService): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validated();
            $employee = $workOSUserService->createEmployee($validated);

            // Handle profile picture upload if provided
            if ($request->hasFile('profile_picture')) {
                $path = $request->file('profile_picture')->store(
                    "profile-pictures/{$employee->id}",
                    'private'
                );
                $employee->update(['profile_picture' => $path]);
            }

            $fullName = "{$employee->first_name} {$employee->last_name}";
            $message = "Employee {$fullName} created successfully.";

            try {
                $workOSUserService->sendInvitation($validated['email'], $validated['role']);
                $message .= ' An invitation email has been sent.';
            } catch (WorkOSException $e) {
                \Log::warning('Failed to send WorkOS invitation', [
                    'email' => $validated['email'],
                    'role' => $validated['role'],
                    'error' => $e->getMessage(),
                    'code' => $e->errorCode,
                ]);
                $message .= ' However, the invitation email could not be sent.';
                if (config('app.debug')) {
                    $message .= " ({$e->errorCode}: {$e->getMessage()})";
                }
            }

            return $this->respondWithCreated(
                $request,
                'users.index',
                [],
                $message,
                new EmployeeResource($employee->fresh())
            );
        } catch (WorkOSException $e) {
            return $this->respondWithError($request, $e->message, $e->toArray(), 422);
        } catch (Throwable $e) {
            $errorData = ['code' => 'unexpected_error'];

            if (config('app.debug')) {
                $errorData['debug'] = sprintf(
                    '%s: %s in %s:%d',
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                );
            }

            return $this->respondWithError(
                $request,
                'An unexpected error occurred while creating the user.',
                $errorData,
                500
            );
        }
    }
}

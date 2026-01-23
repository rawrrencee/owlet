<?php

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use App\Services\WorkOSRoleResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;
use Laravel\WorkOS\User as WorkOSUser;

Route::get('login', function (AuthKitLoginRequest $request) {
    return $request->redirect();
})->middleware(['guest'])->name('login');

Route::get('authenticate', function (AuthKitAuthenticationRequest $request) {
    $roleResolver = app(WorkOSRoleResolver::class);

    $createUsing = function (WorkOSUser $workosUser) use ($roleResolver): User {
        $role = $roleResolver->resolve($workosUser->id);

        return DB::transaction(function () use ($workosUser, $role) {
            $employeeId = null;
            $customerId = null;

            // Create the appropriate record based on role
            // Admin and staff get Employee records, everyone else gets Customer records
            if (in_array($role, ['admin', 'staff'])) {
                $employee = Employee::create([
                    'first_name' => $workosUser->firstName,
                    'last_name' => $workosUser->lastName,
                    'hire_date' => now(),
                ]);
                $employeeId = $employee->id;
            } else {
                $customer = Customer::create([
                    'first_name' => $workosUser->firstName,
                    'last_name' => $workosUser->lastName,
                    'email' => $workosUser->email,
                    'customer_since' => now(),
                ]);
                $customerId = $customer->id;
            }

            return User::create([
                'name' => $workosUser->firstName.' '.$workosUser->lastName,
                'email' => $workosUser->email,
                'email_verified_at' => now(),
                'workos_id' => $workosUser->id,
                'avatar' => $workosUser->avatar ?? '',
                'role' => $role,
                'employee_id' => $employeeId,
                'customer_id' => $customerId,
            ]);
        });
    };

    $updateUsing = function (User $user, WorkOSUser $workosUser) use ($roleResolver): User {
        $role = $roleResolver->resolve($workosUser->id);

        return DB::transaction(function () use ($user, $workosUser, $role) {
            $updates = [
                'avatar' => $workosUser->avatar ?? '',
                'role' => $role,
            ];

            // If role changed to admin/staff and user doesn't have an Employee record, create one
            if (in_array($role, ['admin', 'staff']) && ! $user->employee_id) {
                $employee = Employee::create([
                    'first_name' => $workosUser->firstName,
                    'last_name' => $workosUser->lastName,
                    'hire_date' => now(),
                ]);
                $updates['employee_id'] = $employee->id;
            }

            // If role changed to customer and user doesn't have a Customer record, create one
            if ($role === 'customer' && ! $user->customer_id) {
                $customer = Customer::create([
                    'first_name' => $workosUser->firstName,
                    'last_name' => $workosUser->lastName,
                    'email' => $workosUser->email,
                    'customer_since' => now(),
                ]);
                $updates['customer_id'] = $customer->id;
            }

            return tap($user)->update($updates);
        });
    };

    return tap(
        redirect()->intended(route('dashboard')),
        fn () => $request->authenticate(updateUsing: $updateUsing, createUsing: $createUsing)
    );
})->middleware(['guest']);

Route::post('logout', function (AuthKitLogoutRequest $request) {
    return $request->logout();
})->middleware(['auth'])->name('logout');

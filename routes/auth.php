<?php

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Timecard;
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

            // Check if there's a pending employee invitation for this email
            $pendingEmployee = Employee::where('pending_email', $workosUser->email)->first();

            if ($pendingEmployee) {
                // Link to existing employee created via invitation
                $employeeId = $pendingEmployee->id;
                // Use the role from the invitation if available
                $role = $pendingEmployee->pending_role ?? $role;
                // Clear the pending fields and store WorkOS avatar
                $pendingEmployee->update([
                    'pending_email' => null,
                    'pending_role' => null,
                    'external_avatar_url' => $workosUser->avatar,
                ]);
            } elseif (in_array($role, ['admin', 'staff'])) {
                // Create the appropriate record based on role
                // Admin and staff get Employee records, everyone else gets Customer records
                $employee = Employee::create([
                    'first_name' => $workosUser->firstName,
                    'last_name' => $workosUser->lastName,
                    'hire_date' => now(),
                    'external_avatar_url' => $workosUser->avatar,
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
                'role' => $role,
            ];

            // If role changed to admin/staff and user doesn't have an Employee record, create one
            if (in_array($role, ['admin', 'staff']) && ! $user->employee_id) {
                $employee = Employee::create([
                    'first_name' => $workosUser->firstName,
                    'last_name' => $workosUser->lastName,
                    'hire_date' => now(),
                    'external_avatar_url' => $workosUser->avatar,
                ]);
                $updates['employee_id'] = $employee->id;
            } elseif ($user->employee_id) {
                // Update the external avatar URL on the existing Employee
                Employee::where('id', $user->employee_id)->update([
                    'external_avatar_url' => $workosUser->avatar,
                ]);
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

    $user = $request->authenticate(updateUsing: $updateUsing, createUsing: $createUsing);

    // Mark previous IN_PROGRESS timecards from past days as incomplete
    if ($user->employee_id) {
        Timecard::where('employee_id', $user->employee_id)
            ->where('status', Timecard::STATUS_IN_PROGRESS)
            ->where('is_incomplete', false)
            ->whereDate('start_date', '<', today())
            ->update(['is_incomplete' => true]);
    }

    return redirect()->intended(route('dashboard'));
})->middleware(['guest']);

Route::post('logout', function (AuthKitLogoutRequest $request) {
    return $request->logout();
})->middleware(['auth'])->name('logout');

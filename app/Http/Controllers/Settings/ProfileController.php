<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\WorkOSException;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Services\WorkOSUserService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile page.
     */
    public function edit(Request $request, WorkOSUserService $workOSUserService): Response
    {
        $user = $request->user();
        $employee = null;
        $workosUser = null;
        $role = $user->role ?? 'staff';

        // Load employee data if user is an employee
        if ($user->employee_id) {
            $employee = $user->employee;
            $employee->load([
                'countryOfResidence',
                'nationalityCountry',
            ]);

            // Get WorkOS user info
            if ($user->workos_id) {
                try {
                    $workosUser = $workOSUserService->getUser($user->workos_id);
                    $workosRole = $workOSUserService->getUserRole($user->workos_id);
                    if ($workosRole) {
                        $role = $workosRole;
                    }
                } catch (WorkOSException) {
                    // WorkOS user not found, continue without it
                }
            }
        }

        return Inertia::render('settings/Profile', [
            'employee' => $employee ? (new EmployeeResource($employee))->resolve() : null,
            'workosUser' => $workosUser ? [
                'id' => $workosUser->id,
                'email' => $workosUser->email,
                'emailVerified' => $workosUser->emailVerified,
            ] : null,
            'role' => $role,
        ]);
    }
}

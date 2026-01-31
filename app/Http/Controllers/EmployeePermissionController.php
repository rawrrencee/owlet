<?php

namespace App\Http\Controllers;

use App\Constants\PagePermissions;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Employee;
use App\Models\EmployeePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeePermissionController extends Controller
{
    use RespondsWithInertiaOrJson;

    /**
     * Get the employee's permissions and available permissions.
     */
    public function index(Employee $employee): JsonResponse
    {
        $employeePermission = $employee->permission;

        return response()->json([
            'data' => [
                'page_permissions' => $employeePermission?->page_permissions ?? [],
            ],
            'available_permissions' => PagePermissions::grouped(),
        ]);
    }

    /**
     * Update the employee's permissions.
     */
    public function update(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'page_permissions' => ['nullable', 'array'],
            'page_permissions.*' => ['string', 'in:'.implode(',', PagePermissions::keys())],
        ]);

        $pagePermissions = $validated['page_permissions'] ?? [];

        if (empty($pagePermissions)) {
            // If no permissions, delete the record if it exists
            $employee->permission?->delete();
        } else {
            // Update or create the permission record
            EmployeePermission::updateOrCreate(
                ['employee_id' => $employee->id],
                ['page_permissions' => $pagePermissions]
            );
        }

        $employee->load('permission');

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Permissions updated successfully.',
            ['page_permissions' => $employee->permission?->page_permissions ?? []]
        );
    }
}

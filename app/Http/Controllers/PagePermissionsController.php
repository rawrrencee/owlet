<?php

namespace App\Http\Controllers;

use App\Constants\PagePermissions;
use App\Models\Employee;
use App\Models\EmployeePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagePermissionsController extends Controller
{
    /**
     * Map page identifiers to their permission keys.
     */
    private const PAGE_PERMISSIONS = [
        'brands' => [PagePermissions::BRANDS_VIEW, PagePermissions::BRANDS_MANAGE],
        'categories' => [PagePermissions::CATEGORIES_VIEW, PagePermissions::CATEGORIES_MANAGE],
        'products' => [
            PagePermissions::PRODUCTS_VIEW,
            PagePermissions::PRODUCTS_CREATE,
            PagePermissions::PRODUCTS_EDIT,
            PagePermissions::PRODUCTS_DELETE,
            PagePermissions::PRODUCTS_VIEW_COST_PRICE,
            PagePermissions::PRODUCTS_MANAGE_INVENTORY,
        ],
        'suppliers' => [PagePermissions::SUPPLIERS_VIEW, PagePermissions::SUPPLIERS_MANAGE],
        'stores' => [PagePermissions::STORES_ACCESS, PagePermissions::STORES_MANAGE],
    ];

    /**
     * Get staff users with their permissions for a specific page.
     */
    public function index(string $page): JsonResponse
    {
        if (! isset(self::PAGE_PERMISSIONS[$page])) {
            return response()->json(['error' => 'Invalid page identifier'], 404);
        }

        $pagePermissionKeys = self::PAGE_PERMISSIONS[$page];

        // Get all staff employees
        $staffUsers = Employee::whereHas('user', function ($query) {
            $query->where('role', 'staff');
        })
            ->with(['user', 'permission'])
            ->whereNull('termination_date')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (Employee $employee) use ($pagePermissionKeys) {
                // Filter permissions to only those relevant to this page
                $employeePermissions = $employee->permission?->page_permissions ?? [];
                $relevantPermissions = array_values(array_intersect($employeePermissions, $pagePermissionKeys));

                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'email' => $employee->user?->email,
                    'profile_picture_url' => $employee->getProfilePictureUrl(),
                    'permissions' => $relevantPermissions,
                ];
            });

        // Get the available permissions for this page
        $allPermissions = PagePermissions::all();
        $availablePermissions = collect($pagePermissionKeys)
            ->map(fn ($key) => $allPermissions[$key] ?? null)
            ->filter()
            ->values()
            ->toArray();

        return response()->json([
            'data' => $staffUsers,
            'available_permissions' => $availablePermissions,
        ]);
    }

    /**
     * Bulk update permissions for multiple users on a specific page.
     */
    public function update(Request $request, string $page): JsonResponse
    {
        if (! isset(self::PAGE_PERMISSIONS[$page])) {
            return response()->json(['error' => 'Invalid page identifier'], 404);
        }

        $pagePermissionKeys = self::PAGE_PERMISSIONS[$page];

        $validated = $request->validate([
            'users' => ['required', 'array'],
            'users.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'users.*.permissions' => ['required', 'array'],
            'users.*.permissions.*' => ['string', 'in:'.implode(',', $pagePermissionKeys)],
        ]);

        foreach ($validated['users'] as $userData) {
            $employeeId = $userData['employee_id'];
            $newPagePermissions = $userData['permissions'];

            // Get current permission record
            $permission = EmployeePermission::where('employee_id', $employeeId)->first();
            $currentPermissions = $permission?->page_permissions ?? [];

            // Remove all permissions for this page, then add the new ones
            $updatedPermissions = array_values(array_filter(
                $currentPermissions,
                fn ($p) => ! in_array($p, $pagePermissionKeys)
            ));
            $updatedPermissions = array_values(array_unique(array_merge($updatedPermissions, $newPagePermissions)));

            if (empty($updatedPermissions)) {
                // If no permissions left, delete the record
                $permission?->delete();
            } else {
                // Update or create the permission record
                EmployeePermission::updateOrCreate(
                    ['employee_id' => $employeeId],
                    ['page_permissions' => $updatedPermissions]
                );
            }
        }

        return response()->json([
            'message' => 'Permissions updated successfully.',
        ]);
    }
}

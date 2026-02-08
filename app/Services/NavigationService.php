<?php

namespace App\Services;

use App\Models\User;

class NavigationService
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    public function getMainNavItems(User $user): array
    {
        $sections = [];

        // Platform section - always visible
        $platformItems = [
            ['title' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'LayoutGrid'],
        ];

        // Add My Timecards for all authenticated users with employee record
        if ($user->employee) {
            $platformItems[] = ['title' => 'My Timecards', 'href' => '/timecards', 'icon' => 'Clock'];
        }

        $sections[] = [
            'title' => 'Platform',
            'items' => $platformItems,
        ];

        // My Tools section - for employees with subordinates
        $myToolsItems = [];
        if ($user->employee?->hasSubordinates()) {
            $myToolsItems[] = ['title' => 'My Team', 'href' => '/my-team', 'icon' => 'UsersRound'];
            $myToolsItems[] = ['title' => 'Team Timecards', 'href' => '/my-team-timecards', 'icon' => 'ClipboardList'];
        }

        if (! empty($myToolsItems)) {
            $sections[] = [
                'title' => 'My Tools',
                'items' => $myToolsItems,
            ];
        }

        // Commerce section - before Management
        $commerceItems = [];

        // Check permissions for commerce items
        if ($this->permissionService->canAccessPage($user, 'products.view')) {
            $commerceItems[] = ['title' => 'Products', 'href' => '/products', 'icon' => 'Package'];
        }
        if ($this->permissionService->canAccessPage($user, 'brands.view')) {
            $commerceItems[] = ['title' => 'Brands', 'href' => '/brands', 'icon' => 'Tag'];
        }
        if ($this->permissionService->canAccessPage($user, 'categories.view')) {
            $commerceItems[] = ['title' => 'Categories', 'href' => '/categories', 'icon' => 'Layers'];
        }
        if ($this->permissionService->canAccessPage($user, 'suppliers.view')) {
            $commerceItems[] = ['title' => 'Suppliers', 'href' => '/suppliers', 'icon' => 'Truck'];
        }
        if ($this->permissionService->canAccessPage($user, 'stores.access')) {
            $commerceItems[] = ['title' => 'Stores', 'href' => '/stores', 'icon' => 'Store'];
        }
        if ($this->permissionService->canAccessPage($user, 'stocktakes.submit')) {
            $commerceItems[] = ['title' => 'Stocktake', 'href' => '/stocktakes', 'icon' => 'ClipboardCheck'];
        }

        // Stock Check - available to all authenticated users
        $commerceItems[] = ['title' => 'Stock Check', 'href' => '/stock-check', 'icon' => 'PackageSearch'];

        if (! empty($commerceItems)) {
            $sections[] = [
                'title' => 'Commerce',
                'items' => $commerceItems,
            ];
        }

        // Management section
        $managementItems = [];

        // Stocktakes management - accessible to admins AND staff with permission
        if ($user->isAdmin() || $this->permissionService->canAccessPage($user, 'stocktakes.manage')) {
            $managementItems[] = ['title' => 'Stocktakes', 'href' => '/management/stocktakes', 'icon' => 'ClipboardList'];
        }

        // Admin-only items
        if ($user->isAdmin()) {
            $managementItems[] = ['title' => 'Users', 'href' => '/users', 'icon' => 'Users'];
            $managementItems[] = ['title' => 'Documents', 'href' => '/documents', 'icon' => 'Folder'];
            $managementItems[] = ['title' => 'Timecards', 'href' => '/management/timecards', 'icon' => 'CalendarClock'];
            $managementItems[] = ['title' => 'Companies', 'href' => '/companies', 'icon' => 'Building2'];
            $managementItems[] = ['title' => 'Currencies', 'href' => '/currencies', 'icon' => 'Coins'];
            $managementItems[] = ['title' => 'Designations', 'href' => '/designations', 'icon' => 'BadgeCheck'];
            $managementItems[] = ['title' => 'Organisation Chart', 'href' => '/organisation-chart', 'icon' => 'Network'];
            $managementItems[] = ['title' => 'Notifications', 'href' => '/management/notifications/stocktake', 'icon' => 'BellRing'];
            $managementItems[] = ['title' => 'Data Migration', 'href' => '/admin/data-migration', 'icon' => 'DatabaseZap'];
        }

        // Only add the Management section if there are items
        if (! empty($managementItems)) {
            $sections[] = [
                'title' => 'Management',
                'items' => $managementItems,
            ];
        }

        return $sections;
    }
}

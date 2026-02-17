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

        // Add My Timecards and My Leave for all authenticated users with employee record
        if ($user->employee) {
            $platformItems[] = ['title' => 'My Timecards', 'href' => '/timecards', 'icon' => 'Clock'];
            $platformItems[] = ['title' => 'My Leave', 'href' => '/leave', 'icon' => 'CalendarDays'];
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
            $myToolsItems[] = ['title' => 'Team Leave', 'href' => '/my-team-leave', 'icon' => 'CalendarClock'];
        }

        if (! empty($myToolsItems)) {
            $sections[] = [
                'title' => 'My Tools',
                'items' => $myToolsItems,
            ];
        }

        // Catalog section - product data & master data
        $catalogItems = [];

        if ($this->permissionService->canAccessPage($user, 'products.view')) {
            $catalogItems[] = ['title' => 'Products', 'href' => '/products', 'icon' => 'Package'];
        }
        if ($this->permissionService->canAccessPage($user, 'brands.view')) {
            $catalogItems[] = ['title' => 'Brands', 'href' => '/brands', 'icon' => 'Tag'];
        }
        if ($this->permissionService->canAccessPage($user, 'categories.view')) {
            $catalogItems[] = ['title' => 'Categories', 'href' => '/categories', 'icon' => 'Layers'];
        }
        if ($this->permissionService->canAccessPage($user, 'suppliers.view')) {
            $catalogItems[] = ['title' => 'Suppliers', 'href' => '/suppliers', 'icon' => 'Truck'];
        }

        if (! empty($catalogItems)) {
            $sections[] = [
                'title' => 'Catalog',
                'items' => $catalogItems,
            ];
        }

        // Inventory section - stock operations & logistics
        $inventoryItems = [];

        if ($this->permissionService->canAccessPage($user, 'stores.access')) {
            $inventoryItems[] = ['title' => 'Stores', 'href' => '/stores', 'icon' => 'Store'];
        }

        // Stock Check - available to all authenticated users
        $inventoryItems[] = ['title' => 'Stock Check', 'href' => '/stock-check', 'icon' => 'PackageSearch'];

        if ($this->permissionService->canAccessPage($user, 'stocktakes.submit')) {
            $inventoryItems[] = ['title' => 'Stocktake', 'href' => '/stocktakes', 'icon' => 'ClipboardCheck'];
        }
        if ($this->permissionService->canAccessPage($user, 'delivery_orders.view')) {
            $inventoryItems[] = ['title' => 'Delivery Orders', 'href' => '/delivery-orders', 'icon' => 'ArrowLeftRight'];
        }
        if ($this->permissionService->canAccessPage($user, 'purchase_orders.view')
            || $this->permissionService->canAccessPage($user, 'purchase_orders.create')
            || $this->permissionService->canAccessPage($user, 'purchase_orders.accept')) {
            $inventoryItems[] = ['title' => 'Purchase Orders', 'href' => '/purchase-orders', 'icon' => 'ShoppingCart'];
        }
        if ($this->permissionService->canAccessPage($user, 'inventory_logs.view')) {
            $inventoryItems[] = ['title' => 'Inventory Logs', 'href' => '/inventory-logs', 'icon' => 'ScrollText'];
        }

        if (! empty($inventoryItems)) {
            $sections[] = [
                'title' => 'Inventory',
                'items' => $inventoryItems,
            ];
        }

        // Sales section - customer-facing transactions & payment config
        $salesItems = [];

        if ($this->permissionService->canAccessPage($user, 'pos.access')) {
            $salesItems[] = ['title' => 'Point of Sale', 'href' => '/pos', 'icon' => 'ShoppingBag'];
        }
        if ($this->permissionService->canAccessPage($user, 'transactions.view')) {
            $salesItems[] = ['title' => 'Transactions', 'href' => '/transactions', 'icon' => 'Receipt'];
        }
        if ($this->permissionService->canAccessPage($user, 'offers.view')) {
            $salesItems[] = ['title' => 'Offers', 'href' => '/offers', 'icon' => 'Percent'];
        }
        if ($this->permissionService->canAccessPage($user, 'quotations.view')
            || $this->permissionService->canAccessPage($user, 'quotations.create')
            || $this->permissionService->canAccessPage($user, 'quotations.manage')) {
            $salesItems[] = ['title' => 'Quotations', 'href' => '/quotations', 'icon' => 'FileText'];
        }
        if ($user->isAdmin() || $this->permissionService->canAccessPage($user, 'payment_modes.manage')) {
            $salesItems[] = ['title' => 'Payment Modes', 'href' => '/payment-modes', 'icon' => 'CreditCard'];
        }

        if (! empty($salesItems)) {
            $sections[] = [
                'title' => 'Sales',
                'items' => $salesItems,
            ];
        }

        // Organisation section - HR, company structure, employee records
        $organisationItems = [];

        if ($user->isAdmin()) {
            $organisationItems[] = ['title' => 'Users', 'href' => '/users', 'icon' => 'Users'];
            $organisationItems[] = ['title' => 'Companies', 'href' => '/companies', 'icon' => 'Building2'];
            $organisationItems[] = ['title' => 'Designations', 'href' => '/designations', 'icon' => 'BadgeCheck'];
            $organisationItems[] = ['title' => 'Organisation Chart', 'href' => '/organisation-chart', 'icon' => 'Network'];
            $organisationItems[] = ['title' => 'Documents', 'href' => '/documents', 'icon' => 'Folder'];
            $organisationItems[] = ['title' => 'Timecards', 'href' => '/management/timecards', 'icon' => 'CalendarClock'];
            $organisationItems[] = ['title' => 'Leave Requests', 'href' => '/management/leave', 'icon' => 'CalendarCheck'];
        }

        if (! empty($organisationItems)) {
            $sections[] = [
                'title' => 'Organisation',
                'items' => $organisationItems,
            ];
        }

        // System section - system config & admin tools
        $systemItems = [];

        if ($user->isAdmin()) {
            $systemItems[] = ['title' => 'Currencies', 'href' => '/currencies', 'icon' => 'Coins'];
            $systemItems[] = ['title' => 'Leave Types', 'href' => '/management/leave-types', 'icon' => 'ListChecks'];
        }
        if ($user->isAdmin() || $this->permissionService->canAccessPage($user, 'stocktakes.manage')) {
            $systemItems[] = ['title' => 'Stocktakes', 'href' => '/management/stocktakes', 'icon' => 'ClipboardList'];
        }
        if ($user->isAdmin()) {
            $systemItems[] = ['title' => 'Notifications', 'href' => '/management/notifications', 'icon' => 'BellRing'];
            $systemItems[] = ['title' => 'Data Migration', 'href' => '/admin/data-migration', 'icon' => 'DatabaseZap'];
        }

        if (! empty($systemItems)) {
            $sections[] = [
                'title' => 'System',
                'items' => $systemItems,
            ];
        }

        return $sections;
    }
}

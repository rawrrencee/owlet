<?php

namespace App\Constants;

class PagePermissions
{
    // Brands
    public const BRANDS_VIEW = 'brands.view';

    public const BRANDS_MANAGE = 'brands.manage';

    // Categories
    public const CATEGORIES_VIEW = 'categories.view';

    public const CATEGORIES_MANAGE = 'categories.manage';

    // Products
    public const PRODUCTS_VIEW = 'products.view';

    public const PRODUCTS_CREATE = 'products.create';

    public const PRODUCTS_EDIT = 'products.edit';

    public const PRODUCTS_DELETE = 'products.delete';

    public const PRODUCTS_VIEW_COST_PRICE = 'products.view_cost_price';

    public const PRODUCTS_MANAGE_INVENTORY = 'products.manage_inventory';

    // Suppliers
    public const SUPPLIERS_VIEW = 'suppliers.view';

    public const SUPPLIERS_MANAGE = 'suppliers.manage';

    // Stores
    public const STORES_ACCESS = 'stores.access';

    public const STORES_MANAGE = 'stores.manage';

    // Stocktakes
    public const STOCKTAKES_SUBMIT = 'stocktakes.submit';

    public const STOCKTAKES_MANAGE = 'stocktakes.manage';

    public const STOCKTAKES_LOST_AND_FOUND = 'stocktakes.lost_and_found';

    // Delivery Orders
    public const DELIVERY_ORDERS_VIEW = 'delivery_orders.view';

    public const DELIVERY_ORDERS_SUBMIT = 'delivery_orders.submit';

    public const DELIVERY_ORDERS_MANAGE = 'delivery_orders.manage';

    // Purchase Orders
    public const PURCHASE_ORDERS_VIEW = 'purchase_orders.view';

    public const PURCHASE_ORDERS_CREATE = 'purchase_orders.create';

    public const PURCHASE_ORDERS_ACCEPT = 'purchase_orders.accept';

    // Offers
    public const OFFERS_VIEW = 'offers.view';

    public const OFFERS_MANAGE = 'offers.manage';

    // Quotations
    public const QUOTATIONS_VIEW = 'quotations.view';

    public const QUOTATIONS_CREATE = 'quotations.create';

    public const QUOTATIONS_MANAGE = 'quotations.manage';

    public const QUOTATIONS_ADMIN = 'quotations.admin';

    // Payment Modes
    public const PAYMENT_MODES_VIEW = 'payment_modes.view';

    public const PAYMENT_MODES_MANAGE = 'payment_modes.manage';

    // Leave
    public const LEAVE_REQUESTS_MANAGE = 'leave_requests.manage';

    // Point of Sale
    public const POS_ACCESS = 'pos.access';

    // Transactions
    public const TRANSACTIONS_VIEW = 'transactions.view';

    public const TRANSACTIONS_MANAGE = 'transactions.manage';

    // Analytics
    public const ANALYTICS_VIEW = 'analytics.view';

    // Inventory
    public const INVENTORY_LOGS_VIEW = 'inventory_logs.view';

    /**
     * Get all available permissions with their labels.
     *
     * @return array<string, array{key: string, label: string, group: string}>
     */
    public static function all(): array
    {
        return [
            self::BRANDS_VIEW => [
                'key' => self::BRANDS_VIEW,
                'label' => 'View Brands',
                'group' => 'Brands',
            ],
            self::BRANDS_MANAGE => [
                'key' => self::BRANDS_MANAGE,
                'label' => 'Manage Brands',
                'group' => 'Brands',
            ],
            self::CATEGORIES_VIEW => [
                'key' => self::CATEGORIES_VIEW,
                'label' => 'View Categories',
                'group' => 'Categories',
            ],
            self::CATEGORIES_MANAGE => [
                'key' => self::CATEGORIES_MANAGE,
                'label' => 'Manage Categories',
                'group' => 'Categories',
            ],
            self::PRODUCTS_VIEW => [
                'key' => self::PRODUCTS_VIEW,
                'label' => 'View Products',
                'group' => 'Products',
            ],
            self::PRODUCTS_CREATE => [
                'key' => self::PRODUCTS_CREATE,
                'label' => 'Create Products',
                'group' => 'Products',
            ],
            self::PRODUCTS_EDIT => [
                'key' => self::PRODUCTS_EDIT,
                'label' => 'Edit Products',
                'group' => 'Products',
            ],
            self::PRODUCTS_DELETE => [
                'key' => self::PRODUCTS_DELETE,
                'label' => 'Delete Products',
                'group' => 'Products',
            ],
            self::PRODUCTS_VIEW_COST_PRICE => [
                'key' => self::PRODUCTS_VIEW_COST_PRICE,
                'label' => 'View Cost Price',
                'group' => 'Products',
            ],
            self::PRODUCTS_MANAGE_INVENTORY => [
                'key' => self::PRODUCTS_MANAGE_INVENTORY,
                'label' => 'Manage Inventory',
                'group' => 'Products',
            ],
            self::SUPPLIERS_VIEW => [
                'key' => self::SUPPLIERS_VIEW,
                'label' => 'View Suppliers',
                'group' => 'Suppliers',
            ],
            self::SUPPLIERS_MANAGE => [
                'key' => self::SUPPLIERS_MANAGE,
                'label' => 'Manage Suppliers',
                'group' => 'Suppliers',
            ],
            self::STORES_ACCESS => [
                'key' => self::STORES_ACCESS,
                'label' => 'Access Stores',
                'group' => 'Stores',
            ],
            self::STORES_MANAGE => [
                'key' => self::STORES_MANAGE,
                'label' => 'Manage Stores',
                'group' => 'Stores',
            ],
            self::STOCKTAKES_SUBMIT => [
                'key' => self::STOCKTAKES_SUBMIT,
                'label' => 'Submit Stocktakes',
                'group' => 'Stocktakes',
            ],
            self::STOCKTAKES_MANAGE => [
                'key' => self::STOCKTAKES_MANAGE,
                'label' => 'Manage Stocktakes',
                'group' => 'Stocktakes',
            ],
            self::STOCKTAKES_LOST_AND_FOUND => [
                'key' => self::STOCKTAKES_LOST_AND_FOUND,
                'label' => 'Lost and Found Adjustments',
                'group' => 'Stocktakes',
            ],
            self::DELIVERY_ORDERS_VIEW => [
                'key' => self::DELIVERY_ORDERS_VIEW,
                'label' => 'View Delivery Orders',
                'group' => 'Delivery Orders',
            ],
            self::DELIVERY_ORDERS_SUBMIT => [
                'key' => self::DELIVERY_ORDERS_SUBMIT,
                'label' => 'Submit Delivery Orders',
                'group' => 'Delivery Orders',
            ],
            self::DELIVERY_ORDERS_MANAGE => [
                'key' => self::DELIVERY_ORDERS_MANAGE,
                'label' => 'Manage Delivery Orders',
                'group' => 'Delivery Orders',
            ],
            self::PURCHASE_ORDERS_VIEW => [
                'key' => self::PURCHASE_ORDERS_VIEW,
                'label' => 'View Purchase Orders',
                'group' => 'Purchase Orders',
            ],
            self::PURCHASE_ORDERS_CREATE => [
                'key' => self::PURCHASE_ORDERS_CREATE,
                'label' => 'Create Purchase Orders',
                'group' => 'Purchase Orders',
            ],
            self::PURCHASE_ORDERS_ACCEPT => [
                'key' => self::PURCHASE_ORDERS_ACCEPT,
                'label' => 'Accept Purchase Orders',
                'group' => 'Purchase Orders',
            ],
            self::OFFERS_VIEW => [
                'key' => self::OFFERS_VIEW,
                'label' => 'View Offers',
                'group' => 'Offers',
            ],
            self::OFFERS_MANAGE => [
                'key' => self::OFFERS_MANAGE,
                'label' => 'Manage Offers',
                'group' => 'Offers',
            ],
            self::QUOTATIONS_VIEW => [
                'key' => self::QUOTATIONS_VIEW,
                'label' => 'View Quotations',
                'group' => 'Quotations',
            ],
            self::QUOTATIONS_CREATE => [
                'key' => self::QUOTATIONS_CREATE,
                'label' => 'Create Quotations',
                'group' => 'Quotations',
            ],
            self::QUOTATIONS_MANAGE => [
                'key' => self::QUOTATIONS_MANAGE,
                'label' => 'Manage Quotations',
                'group' => 'Quotations',
            ],
            self::QUOTATIONS_ADMIN => [
                'key' => self::QUOTATIONS_ADMIN,
                'label' => 'Administer Quotations',
                'group' => 'Quotations',
            ],
            self::PAYMENT_MODES_VIEW => [
                'key' => self::PAYMENT_MODES_VIEW,
                'label' => 'View Payment Modes',
                'group' => 'Payment Modes',
            ],
            self::PAYMENT_MODES_MANAGE => [
                'key' => self::PAYMENT_MODES_MANAGE,
                'label' => 'Manage Payment Modes',
                'group' => 'Payment Modes',
            ],
            self::LEAVE_REQUESTS_MANAGE => [
                'key' => self::LEAVE_REQUESTS_MANAGE,
                'label' => 'Manage Leave Requests',
                'group' => 'Leave',
            ],
            self::POS_ACCESS => [
                'key' => self::POS_ACCESS,
                'label' => 'Access Point of Sale',
                'group' => 'Point of Sale',
            ],
            self::TRANSACTIONS_VIEW => [
                'key' => self::TRANSACTIONS_VIEW,
                'label' => 'View Transactions',
                'group' => 'Transactions',
            ],
            self::TRANSACTIONS_MANAGE => [
                'key' => self::TRANSACTIONS_MANAGE,
                'label' => 'Manage Transactions',
                'group' => 'Transactions',
            ],
            self::ANALYTICS_VIEW => [
                'key' => self::ANALYTICS_VIEW,
                'label' => 'View Analytics',
                'group' => 'Analytics',
            ],
            self::INVENTORY_LOGS_VIEW => [
                'key' => self::INVENTORY_LOGS_VIEW,
                'label' => 'View Inventory Logs',
                'group' => 'Inventory',
            ],
        ];
    }

    /**
     * Get all permission keys.
     *
     * @return array<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * Get permissions grouped by their group.
     *
     * @return array<string, array<array{key: string, label: string, group: string}>>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $permission) {
            $grouped[$permission['group']][] = $permission;
        }

        return $grouped;
    }

    /**
     * Validate that all given permissions are valid.
     *
     * @param  array<string>  $permissions
     */
    public static function validate(array $permissions): bool
    {
        $validKeys = self::keys();

        return collect($permissions)->every(fn ($permission) => in_array($permission, $validKeys));
    }
}

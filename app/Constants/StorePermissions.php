<?php

namespace App\Constants;

class StorePermissions
{
    // View permissions
    public const VIEW_TRANSACTIONS = 'view_transactions';

    public const VIEW_INVENTORY = 'view_inventory';

    public const VIEW_REPORTS = 'view_reports';

    // Delivery order permissions
    public const ADD_DELIVERY_ORDER = 'add_delivery_order';

    public const EDIT_DELIVERY_ORDER = 'edit_delivery_order';

    public const APPROVE_DELIVERY_ORDER = 'approve_delivery_order';

    public const DELETE_DELIVERY_ORDER = 'delete_delivery_order';

    // Sales permissions
    public const PROCESS_SALES = 'process_sales';

    public const VOID_SALES = 'void_sales';

    public const APPLY_DISCOUNTS = 'apply_discounts';

    // Purchase order permissions
    public const ACCEPT_PURCHASE_ORDER = 'accept_purchase_order';

    // Inventory permissions
    public const MANAGE_INVENTORY = 'manage_inventory';

    public const STOCK_TRANSFER = 'stock_transfer';

    public const STOCKTAKE = 'stocktake';

    /**
     * Get all available permissions with their labels.
     *
     * @return array<string, array{key: string, label: string, group: string}>
     */
    public static function all(): array
    {
        return [
            self::VIEW_TRANSACTIONS => [
                'key' => self::VIEW_TRANSACTIONS,
                'label' => 'View Transactions',
                'group' => 'View',
            ],
            self::VIEW_INVENTORY => [
                'key' => self::VIEW_INVENTORY,
                'label' => 'View Inventory',
                'group' => 'View',
            ],
            self::VIEW_REPORTS => [
                'key' => self::VIEW_REPORTS,
                'label' => 'View Reports',
                'group' => 'View',
            ],
            self::ADD_DELIVERY_ORDER => [
                'key' => self::ADD_DELIVERY_ORDER,
                'label' => 'Add Delivery Order',
                'group' => 'Delivery Orders',
            ],
            self::EDIT_DELIVERY_ORDER => [
                'key' => self::EDIT_DELIVERY_ORDER,
                'label' => 'Edit Delivery Order',
                'group' => 'Delivery Orders',
            ],
            self::APPROVE_DELIVERY_ORDER => [
                'key' => self::APPROVE_DELIVERY_ORDER,
                'label' => 'Approve Delivery Order',
                'group' => 'Delivery Orders',
            ],
            self::DELETE_DELIVERY_ORDER => [
                'key' => self::DELETE_DELIVERY_ORDER,
                'label' => 'Delete Delivery Order',
                'group' => 'Delivery Orders',
            ],
            self::PROCESS_SALES => [
                'key' => self::PROCESS_SALES,
                'label' => 'Process Sales',
                'group' => 'Sales',
            ],
            self::VOID_SALES => [
                'key' => self::VOID_SALES,
                'label' => 'Void Sales',
                'group' => 'Sales',
            ],
            self::APPLY_DISCOUNTS => [
                'key' => self::APPLY_DISCOUNTS,
                'label' => 'Apply Discounts',
                'group' => 'Sales',
            ],
            self::MANAGE_INVENTORY => [
                'key' => self::MANAGE_INVENTORY,
                'label' => 'Manage Inventory',
                'group' => 'Inventory',
            ],
            self::STOCK_TRANSFER => [
                'key' => self::STOCK_TRANSFER,
                'label' => 'Stock Transfer',
                'group' => 'Inventory',
            ],
            self::STOCKTAKE => [
                'key' => self::STOCKTAKE,
                'label' => 'Stocktake',
                'group' => 'Inventory',
            ],
            self::ACCEPT_PURCHASE_ORDER => [
                'key' => self::ACCEPT_PURCHASE_ORDER,
                'label' => 'Accept Purchase Order',
                'group' => 'Purchase Orders',
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

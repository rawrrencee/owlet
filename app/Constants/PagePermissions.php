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

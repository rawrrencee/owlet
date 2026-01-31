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

    // Suppliers
    public const SUPPLIERS_VIEW = 'suppliers.view';

    public const SUPPLIERS_MANAGE = 'suppliers.manage';

    // Stores
    public const STORES_ACCESS = 'stores.access';

    public const STORES_MANAGE = 'stores.manage';

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

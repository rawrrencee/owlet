<?php

namespace App\Constants;

class StoreAccessPermissions
{
    // Store access
    public const STORE_VIEW = 'store.view';

    public const STORE_EDIT = 'store.edit';

    public const STORE_MANAGE_EMPLOYEES = 'store.manage_employees';

    public const STORE_MANAGE_CURRENCIES = 'store.manage_currencies';

    /**
     * Get all available permissions with their labels.
     *
     * @return array<string, array{key: string, label: string, group: string}>
     */
    public static function all(): array
    {
        return [
            self::STORE_VIEW => [
                'key' => self::STORE_VIEW,
                'label' => 'View Store Details',
                'group' => 'Store Access',
            ],
            self::STORE_EDIT => [
                'key' => self::STORE_EDIT,
                'label' => 'Edit Store Settings',
                'group' => 'Store Access',
            ],
            self::STORE_MANAGE_EMPLOYEES => [
                'key' => self::STORE_MANAGE_EMPLOYEES,
                'label' => 'Manage Employees',
                'group' => 'Store Access',
            ],
            self::STORE_MANAGE_CURRENCIES => [
                'key' => self::STORE_MANAGE_CURRENCIES,
                'label' => 'Manage Currencies',
                'group' => 'Store Access',
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

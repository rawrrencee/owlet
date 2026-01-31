<?php

namespace App\Models;

use App\Constants\StoreAccessPermissions;
use App\Constants\StorePermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeStore extends Model
{
    protected $table = 'employee_stores';

    protected $fillable = [
        'employee_id',
        'store_id',
        'active',
        'permissions',
        'access_permissions',
        'is_creator',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'permissions' => 'array',
            'access_permissions' => 'array',
            'is_creator' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Check if this employee-store assignment has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if this employee-store assignment has all of the given permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return collect($permissions)->every(fn ($permission) => $this->hasPermission($permission));
    }

    /**
     * Check if this employee-store assignment has any of the given permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return collect($permissions)->some(fn ($permission) => $this->hasPermission($permission));
    }

    /**
     * Get the permissions with their labels.
     *
     * @return array<array{key: string, label: string, group: string}>
     */
    public function getPermissionsWithLabels(): array
    {
        $allPermissions = StorePermissions::all();

        return collect($this->permissions ?? [])
            ->filter(fn ($permission) => isset($allPermissions[$permission]))
            ->map(fn ($permission) => $allPermissions[$permission])
            ->values()
            ->toArray();
    }

    /**
     * Check if this employee-store assignment has a specific access permission.
     */
    public function hasAccessPermission(string $permission): bool
    {
        return in_array($permission, $this->access_permissions ?? []);
    }

    /**
     * Check if this employee-store assignment has all of the given access permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAllAccessPermissions(array $permissions): bool
    {
        return collect($permissions)->every(fn ($permission) => $this->hasAccessPermission($permission));
    }

    /**
     * Check if this employee-store assignment has any of the given access permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAnyAccessPermission(array $permissions): bool
    {
        return collect($permissions)->some(fn ($permission) => $this->hasAccessPermission($permission));
    }

    /**
     * Get the access permissions with their labels.
     *
     * @return array<array{key: string, label: string, group: string}>
     */
    public function getAccessPermissionsWithLabels(): array
    {
        $allPermissions = StoreAccessPermissions::all();

        return collect($this->access_permissions ?? [])
            ->filter(fn ($permission) => isset($allPermissions[$permission]))
            ->map(fn ($permission) => $allPermissions[$permission])
            ->values()
            ->toArray();
    }
}

<?php

namespace App\Models;

use App\Constants\PagePermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePermission extends Model
{
    protected $table = 'employee_permissions';

    protected $fillable = [
        'employee_id',
        'page_permissions',
    ];

    protected function casts(): array
    {
        return [
            'page_permissions' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if this employee has a specific page permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->page_permissions ?? []);
    }

    /**
     * Check if this employee has all of the given page permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return collect($permissions)->every(fn ($permission) => $this->hasPermission($permission));
    }

    /**
     * Check if this employee has any of the given page permissions.
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
        $allPermissions = PagePermissions::all();

        return collect($this->page_permissions ?? [])
            ->filter(fn ($permission) => isset($allPermissions[$permission]))
            ->map(fn ($permission) => $allPermissions[$permission])
            ->values()
            ->toArray();
    }
}

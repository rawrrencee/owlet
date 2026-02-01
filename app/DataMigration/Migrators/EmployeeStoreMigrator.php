<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\UserStore as LegacyUserStore;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\EmployeeStore;
use Illuminate\Database\Eloquent\Model;

class EmployeeStoreMigrator extends BaseMigrator
{
    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
    }

    public function getModelType(): string
    {
        return 'employee_store';
    }

    public function getDisplayName(): string
    {
        return 'Employee-Store Assignments';
    }

    public function getDependencies(): array
    {
        return ['employee', 'store'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyUserStore::class;
    }

    protected function getOwletModelClass(): string
    {
        return EmployeeStore::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy user_id to Owlet employee_id
        $owletEmployeeId = $this->lookupOwletId('employee', $legacyRecord->user_id);
        if (! $owletEmployeeId) {
            // User might not be migrated (e.g., customer)
            return null;
        }

        // Map legacy store_id to Owlet store_id
        $owletStoreId = $this->lookupOwletId('store', $legacyRecord->store_id);
        if (! $owletStoreId) {
            throw new \Exception("Store #{$legacyRecord->store_id} not migrated yet");
        }

        // Legacy system doesn't have granular permissions, so we give full access
        // These can be adjusted manually after migration if needed
        $defaultPermissions = [
            'manage_products',
            'view_reports',
            'manage_inventory',
            'process_sales',
        ];

        return [
            'employee_id' => $owletEmployeeId,
            'store_id' => $owletStoreId,
            'active' => (bool) $legacyRecord->active,
            'permissions' => $defaultPermissions,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
        ];
    }
}

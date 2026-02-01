<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\UserTeam as LegacyUserTeam;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\EmployeeHierarchy;
use Illuminate\Database\Eloquent\Model;

class EmployeeHierarchyMigrator extends BaseMigrator
{
    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
    }

    public function getModelType(): string
    {
        return 'employee_hierarchy';
    }

    public function getDisplayName(): string
    {
        return 'Employee Hierarchy';
    }

    public function getDependencies(): array
    {
        return ['employee'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyUserTeam::class;
    }

    protected function getOwletModelClass(): string
    {
        return EmployeeHierarchy::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy leader_id to Owlet manager_id (employee_id)
        $owletManagerId = $this->lookupOwletId('employee', $legacyRecord->leader_id);
        if (! $owletManagerId) {
            // Leader might not be migrated
            return null;
        }

        // Map legacy member_id to Owlet subordinate_id (employee_id)
        $owletSubordinateId = $this->lookupOwletId('employee', $legacyRecord->member_id);
        if (! $owletSubordinateId) {
            // Member might not be migrated
            return null;
        }

        return [
            'manager_id' => $owletManagerId,
            'subordinate_id' => $owletSubordinateId,
            'active' => (bool) ($legacyRecord->active ?? true),
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
        ];
    }
}

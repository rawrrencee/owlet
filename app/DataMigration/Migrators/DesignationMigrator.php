<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Designation as LegacyDesignation;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Model;

class DesignationMigrator extends BaseMigrator
{
    public function getModelType(): string
    {
        return 'designation';
    }

    public function getDisplayName(): string
    {
        return 'Designations';
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyDesignation::class;
    }

    protected function getOwletModelClass(): string
    {
        return Designation::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        return [
            'designation_name' => $legacyRecord->designation_name,
            'designation_code' => $legacyRecord->designation_code,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            'deleted_at' => $legacyRecord->deleted_at,
        ];
    }
}

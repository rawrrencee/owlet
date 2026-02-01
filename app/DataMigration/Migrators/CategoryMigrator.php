<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Category as LegacyCategory;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class CategoryMigrator extends BaseMigrator
{
    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
    }

    public function getModelType(): string
    {
        return 'category';
    }

    public function getDisplayName(): string
    {
        return 'Categories';
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyCategory::class;
    }

    protected function getOwletModelClass(): string
    {
        return Category::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        return [
            'category_name' => $legacyRecord->category_name,
            'category_code' => strtoupper($legacyRecord->category_code),
            'description' => $legacyRecord->description,
            'is_active' => true, // Legacy doesn't have active field, default to true
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            'deleted_at' => $legacyRecord->deleted_at,
        ];
    }
}

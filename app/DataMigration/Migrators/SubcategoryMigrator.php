<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Subcategory as LegacySubcategory;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Model;

class SubcategoryMigrator extends BaseMigrator
{
    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
    }

    public function getModelType(): string
    {
        return 'subcategory';
    }

    public function getDisplayName(): string
    {
        return 'Subcategories';
    }

    public function getDependencies(): array
    {
        return ['category'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacySubcategory::class;
    }

    protected function getOwletModelClass(): string
    {
        return Subcategory::class;
    }

    protected function getLegacyQuery()
    {
        // Exclude subcategories whose parent category is deleted
        return LegacySubcategory::query()
            ->whereHas('category');
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy category_id to Owlet category_id
        $owletCategoryId = $this->lookupOwletId('category', $legacyRecord->category_id);
        if (! $owletCategoryId) {
            throw new \Exception("Category #{$legacyRecord->category_id} not migrated yet");
        }

        return [
            'category_id' => $owletCategoryId,
            'subcategory_name' => $legacyRecord->subcategory_name,
            'subcategory_code' => strtoupper($legacyRecord->subcategory_code ?? ''),
            'description' => $legacyRecord->description,
            'is_default' => false, // Legacy doesn't have this field
            'is_active' => true, // Legacy doesn't have active field, default to true
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
        ];
    }
}

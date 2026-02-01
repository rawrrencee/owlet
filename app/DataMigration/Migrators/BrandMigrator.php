<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Brand as LegacyBrand;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;

class BrandMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'brand';
    }

    public function getDisplayName(): string
    {
        return 'Brands';
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyBrand::class;
    }

    protected function getOwletModelClass(): string
    {
        return Brand::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        $data = [
            'brand_name' => $legacyRecord->brand_name,
            'brand_code' => strtoupper($legacyRecord->brand_code),
            'address_1' => $legacyRecord->address_1,
            'address_2' => $legacyRecord->address_2,
            'email' => $legacyRecord->email,
            'phone_number' => $legacyRecord->phone_number,
            'mobile_number' => $legacyRecord->mobile_number,
            'website' => $legacyRecord->website,
            'is_active' => true, // Legacy doesn't have active field
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            'deleted_at' => $legacyRecord->deleted_at,
        ];

        // Migrate logo if exists
        if ($legacyRecord->img_url) {
            $result = $this->imageMigrationService->downloadAndStore(
                $legacyRecord->img_url,
                'brands/logos',
                "brand-{$legacyRecord->id}"
            );
            if ($result) {
                $data['logo_path'] = $result['path'];
                $data['logo_filename'] = $result['filename'];
                $data['logo_mime_type'] = $result['mime_type'];
            }
        }

        return $data;
    }
}

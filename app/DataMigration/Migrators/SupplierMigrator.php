<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Supplier as LegacySupplier;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class SupplierMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'supplier';
    }

    public function getDisplayName(): string
    {
        return 'Suppliers';
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacySupplier::class;
    }

    protected function getOwletModelClass(): string
    {
        return Supplier::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        $data = [
            'supplier_name' => $legacyRecord->supplier_name,
            'address_1' => $legacyRecord->address_1,
            'address_2' => $legacyRecord->address_2,
            'email' => $legacyRecord->email,
            'phone_number' => $legacyRecord->phone_number,
            'mobile_number' => $legacyRecord->mobile_number,
            'website' => $legacyRecord->website,
            'active' => (bool) $legacyRecord->active,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            'deleted_at' => $legacyRecord->deleted_at,
        ];

        // Migrate logo if exists
        if ($legacyRecord->img_url) {
            $result = $this->imageMigrationService->downloadAndStore(
                $legacyRecord->img_url,
                'suppliers/logos',
                "supplier-{$legacyRecord->id}"
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

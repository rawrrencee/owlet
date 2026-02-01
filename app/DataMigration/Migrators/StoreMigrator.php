<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Store as LegacyStore;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;

class StoreMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'store';
    }

    public function getDisplayName(): string
    {
        return 'Stores';
    }

    public function getDependencies(): array
    {
        return ['company'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyStore::class;
    }

    protected function getOwletModelClass(): string
    {
        return Store::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy company_id to Owlet company_id
        $owletCompanyId = $this->lookupOwletId('company', $legacyRecord->company_id);
        if (! $owletCompanyId) {
            throw new \Exception("Company #{$legacyRecord->company_id} not migrated yet");
        }

        $data = [
            'store_name' => $legacyRecord->store_name,
            'store_code' => strtoupper($legacyRecord->store_code),
            'company_id' => $owletCompanyId,
            'address_1' => $legacyRecord->address_1,
            'address_2' => $legacyRecord->address_2,
            'email' => $legacyRecord->email,
            'phone_number' => $legacyRecord->phone_number,
            'mobile_number' => $legacyRecord->mobile_number,
            'website' => $legacyRecord->website,
            'active' => (bool) $legacyRecord->active,
            'include_tax' => (bool) $legacyRecord->include_tax,
            'tax_percentage' => $legacyRecord->tax_percentage ?? 0,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            'deleted_at' => $legacyRecord->deleted_at,
        ];

        // Migrate logo if exists
        if ($legacyRecord->img_url) {
            $result = $this->imageMigrationService->downloadAndStore(
                $legacyRecord->img_url,
                'stores/logos',
                "store-{$legacyRecord->id}"
            );
            if ($result) {
                $data['logo'] = $result['path'];
            }
        }

        return $data;
    }
}

<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Company as LegacyCompany;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

class CompanyMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'company';
    }

    public function getDisplayName(): string
    {
        return 'Companies';
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyCompany::class;
    }

    protected function getOwletModelClass(): string
    {
        return Company::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        $data = [
            'company_name' => $legacyRecord->company_name,
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
                'companies/logos',
                "company-{$legacyRecord->id}"
            );
            if ($result) {
                $data['logo'] = $result['path'];
            }
        }

        return $data;
    }
}

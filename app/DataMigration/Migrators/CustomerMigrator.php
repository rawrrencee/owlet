<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Customer as LegacyCustomer;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CustomerMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'customer';
    }

    public function getDisplayName(): string
    {
        return 'Customers';
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyCustomer::class;
    }

    protected function getOwletModelClass(): string
    {
        return Customer::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        $data = [
            'first_name' => $legacyRecord->first_name ?? '',
            'last_name' => $legacyRecord->last_name ?? '',
            'email' => $legacyRecord->email,
            'phone' => $legacyRecord->phone_number,
            'mobile' => $legacyRecord->mobile_number,
            'date_of_birth' => $legacyRecord->date_of_birth,
            'gender' => $legacyRecord->gender,
            'race' => $legacyRecord->race,
            // Note: Legacy has country/city/state/address as separate fields but no country_id
            // Owlet uses country_id and nationality_id - these will be null for migrated records
            'discount_percentage' => $legacyRecord->discount_percentage ?? 0,
            'notes' => $legacyRecord->comments, // Legacy 'comments' -> Owlet 'notes'
            // Skipping: company_name (not in Owlet schema as confirmed in plan)
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            'deleted_at' => $legacyRecord->deleted_at,
        ];

        // Migrate customer image if exists
        if ($legacyRecord->img_url) {
            $result = $this->imageMigrationService->downloadAndStore(
                $legacyRecord->img_url,
                'customers/images',
                "customer-{$legacyRecord->id}"
            );
            if ($result) {
                $data['image_url'] = $result['path'];
            }
        }

        return $data;
    }

    protected function getLogMetadata(Model $legacyRecord, Model $owletModel): ?array
    {
        // Store skipped fields for reference
        return [
            'legacy_company_name' => $legacyRecord->company_name,
            'legacy_country' => $legacyRecord->country,
            'legacy_city' => $legacyRecord->city,
            'legacy_state' => $legacyRecord->state,
            'legacy_address_1' => $legacyRecord->address_1,
            'legacy_address_2' => $legacyRecord->address_2,
            'legacy_postal_code' => $legacyRecord->postal_code,
        ];
    }
}

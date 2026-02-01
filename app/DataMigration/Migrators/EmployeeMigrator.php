<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\User as LegacyUser;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;

class EmployeeMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'employee';
    }

    public function getDisplayName(): string
    {
        return 'Employees';
    }

    public function getDependencies(): array
    {
        return [];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyUser::class;
    }

    protected function getOwletModelClass(): string
    {
        return Employee::class;
    }

    protected function getLegacyQuery()
    {
        // Migrate all users (SUPERADMIN, ADMIN, EMPLOYEE)
        // Legacy system uses uppercase role values
        return LegacyUser::query()
            ->whereIn('role', ['SUPERADMIN', 'ADMIN', 'EMPLOYEE'])
            ->with('userProfile');
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        $profile = $legacyRecord->userProfile;

        // Build employee data from User and UserProfile
        $data = [
            'first_name' => $profile?->first_name ?? '',
            'last_name' => $profile?->last_name ?? '',
            'chinese_name' => $profile?->chinese_name,
            'nric' => $profile?->nric,
            'phone' => $profile?->phone_number,
            'mobile' => $profile?->mobile_number,
            'address_1' => $profile?->address_1,
            'address_2' => $profile?->address_2,
            'city' => $profile?->city,
            'state' => $profile?->state,
            'postal_code' => $profile?->postal_code,
            'country' => $profile?->country,
            'date_of_birth' => $profile?->date_of_birth,
            'gender' => $profile?->gender,
            'race' => $profile?->race,
            'nationality' => $profile?->nationality,
            'residency_status' => $profile?->residency_status,
            'pr_conversion_date' => $profile?->pr_conversion_date,
            'emergency_name' => $profile?->emergency_name,
            'emergency_relationship' => $profile?->emergency_relationship,
            'emergency_contact' => $profile?->emergency_contact,
            'emergency_address_1' => $profile?->emergency_address_1,
            'emergency_address_2' => $profile?->emergency_address_2,
            'bank_name' => $profile?->bank_name,
            'bank_account_number' => $profile?->bank_account_number,
            'notes' => $profile?->comments,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            'deleted_at' => $legacyRecord->deleted_at,
        ];

        // Migrate profile picture if exists
        if ($profile?->img_url) {
            $result = $this->imageMigrationService->downloadAndStore(
                $profile->img_url,
                'employees/profile-pictures',
                "employee-{$legacyRecord->id}"
            );
            if ($result) {
                $data['profile_picture'] = $result['path'];
            }
        }

        return $data;
    }

    protected function getLogMetadata(Model $legacyRecord, Model $owletModel): ?array
    {
        return [
            'legacy_email' => $legacyRecord->email,
            'legacy_role' => $legacyRecord->role,
            'legacy_username' => $legacyRecord->username,
        ];
    }
}

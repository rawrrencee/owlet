<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\UserInsurance as LegacyUserInsurance;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\EmployeeInsurance;
use Illuminate\Database\Eloquent\Model;

class EmployeeInsuranceMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'employee_insurance';
    }

    public function getDisplayName(): string
    {
        return 'Employee Insurances';
    }

    public function getDependencies(): array
    {
        return ['employee'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyUserInsurance::class;
    }

    protected function getOwletModelClass(): string
    {
        return EmployeeInsurance::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy user_id to Owlet employee_id
        $owletEmployeeId = $this->lookupOwletId('employee', $legacyRecord->user_id);
        if (! $owletEmployeeId) {
            // User might not be migrated (e.g., customer)
            return null;
        }

        $data = [
            'employee_id' => $owletEmployeeId,
            'title' => $legacyRecord->title,
            'insurer_name' => $legacyRecord->insurer_name,
            'policy_number' => $legacyRecord->policy_number,
            'start_date' => $legacyRecord->start_date,
            'end_date' => $legacyRecord->end_date,
            'external_document_url' => $legacyRecord->ext_document_url,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
        ];

        // Migrate document if has local upload
        if ($legacyRecord->has_upload && $legacyRecord->upload_url) {
            $result = $this->imageMigrationService->downloadDocument(
                $legacyRecord->upload_url,
                'employees/insurances',
                "insurance-{$legacyRecord->id}"
            );
            if ($result) {
                $data['document_path'] = $result['path'];
                $data['document_filename'] = $result['filename'];
                $data['document_mime_type'] = $result['mime_type'];
            }
        }

        return $data;
    }
}

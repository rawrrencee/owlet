<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\UserContract as LegacyUserContract;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\EmployeeContract;
use Illuminate\Database\Eloquent\Model;

class EmployeeContractMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'employee_contract';
    }

    public function getDisplayName(): string
    {
        return 'Employee Contracts';
    }

    public function getDependencies(): array
    {
        return ['employee', 'company'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyUserContract::class;
    }

    protected function getOwletModelClass(): string
    {
        return EmployeeContract::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy user_id to Owlet employee_id
        $owletEmployeeId = $this->lookupOwletId('employee', $legacyRecord->user_id);
        if (! $owletEmployeeId) {
            // User might not be migrated (e.g., customer)
            return null;
        }

        // Map legacy company_id to Owlet company_id
        $owletCompanyId = null;
        if ($legacyRecord->company_id) {
            $owletCompanyId = $this->lookupOwletId('company', $legacyRecord->company_id);
        }

        $data = [
            'employee_id' => $owletEmployeeId,
            'company_id' => $owletCompanyId,
            'start_date' => $legacyRecord->start_date,
            'end_date' => $legacyRecord->end_date,
            'salary_amount' => $legacyRecord->salary_amount ?? 0,
            'annual_leave_entitled' => $legacyRecord->annual_leave_entitled ?? 0,
            'annual_leave_taken' => $legacyRecord->annual_leave_taken ?? 0,
            'sick_leave_entitled' => $legacyRecord->sick_leave_entitled ?? 0,
            'sick_leave_taken' => $legacyRecord->sick_leave_taken ?? 0,
            'external_document_url' => $legacyRecord->ext_document_url,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
        ];

        // Migrate document if has local upload
        if ($legacyRecord->has_upload && $legacyRecord->upload_url) {
            $result = $this->imageMigrationService->downloadDocument(
                $legacyRecord->upload_url,
                'employees/contracts',
                "contract-{$legacyRecord->id}"
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

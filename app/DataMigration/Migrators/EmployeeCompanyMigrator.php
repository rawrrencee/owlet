<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\UserCompany as LegacyUserCompany;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\EmployeeCompany;
use Illuminate\Database\Eloquent\Model;

class EmployeeCompanyMigrator extends BaseMigrator
{
    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
    }

    public function getModelType(): string
    {
        return 'employee_company';
    }

    public function getDisplayName(): string
    {
        return 'Employee-Company Assignments';
    }

    public function getDependencies(): array
    {
        return ['employee', 'company', 'designation'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyUserCompany::class;
    }

    protected function getOwletModelClass(): string
    {
        return EmployeeCompany::class;
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
        $owletCompanyId = $this->lookupOwletId('company', $legacyRecord->company_id);
        if (! $owletCompanyId) {
            throw new \Exception("Company #{$legacyRecord->company_id} not migrated yet");
        }

        // Map legacy designation_id to Owlet designation_id
        $owletDesignationId = null;
        if ($legacyRecord->designation_id) {
            $owletDesignationId = $this->lookupOwletId('designation', $legacyRecord->designation_id);
            // Designation might not exist, that's okay
        }

        return [
            'employee_id' => $owletEmployeeId,
            'company_id' => $owletCompanyId,
            'designation_id' => $owletDesignationId,
            'levy_amount' => $legacyRecord->levy_amount ?? 0,
            'status' => $legacyRecord->status,
            'include_shg_donations' => (bool) $legacyRecord->include_shg_donations,
            'commencement_date' => $legacyRecord->commencement_date,
            'left_date' => $legacyRecord->left_date,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
        ];
    }
}

<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Timecard as LegacyTimecard;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Timecard;
use Illuminate\Database\Eloquent\Model;

class TimecardMigrator extends BaseMigrator
{
    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
    }

    public function getModelType(): string
    {
        return 'timecard';
    }

    public function getDisplayName(): string
    {
        return 'Timecards';
    }

    public function getDependencies(): array
    {
        return ['employee', 'store'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyTimecard::class;
    }

    protected function getOwletModelClass(): string
    {
        return Timecard::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy user_id to Owlet employee_id
        $owletEmployeeId = $this->lookupOwletId('employee', $legacyRecord->user_id);
        if (! $owletEmployeeId) {
            // User might not be migrated (e.g., customer)
            return null;
        }

        // Map legacy store_id to Owlet store_id
        $owletStoreId = $this->lookupOwletId('store', $legacyRecord->store_id);
        if (! $owletStoreId) {
            throw new \Exception("Store #{$legacyRecord->store_id} not migrated yet");
        }

        // Map legacy status to Owlet status
        $status = $this->mapStatus($legacyRecord->status);

        return [
            'employee_id' => $owletEmployeeId,
            'store_id' => $owletStoreId,
            'status' => $status,
            'is_incomplete' => false, // Legacy doesn't have this concept
            'is_inaccurate' => false, // Legacy doesn't have this concept
            'start_date' => $legacyRecord->start_date,
            'end_date' => $legacyRecord->end_date,
            'hours_worked' => $legacyRecord->hours_worked ?? 0,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
        ];
    }

    /**
     * Map legacy status to Owlet status.
     */
    protected function mapStatus(?string $legacyStatus): string
    {
        // Legacy statuses are typically: 'IN_PROGRESS', 'COMPLETED', etc.
        return match (strtoupper($legacyStatus ?? '')) {
            'IN_PROGRESS', 'WORKING', 'ACTIVE' => Timecard::STATUS_IN_PROGRESS,
            'COMPLETED', 'DONE', 'FINISHED' => Timecard::STATUS_COMPLETED,
            'EXPIRED' => Timecard::STATUS_EXPIRED,
            default => Timecard::STATUS_COMPLETED, // Default to completed for unknown statuses
        };
    }
}

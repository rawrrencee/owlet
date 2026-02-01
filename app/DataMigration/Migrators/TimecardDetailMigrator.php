<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\TimecardDetail as LegacyTimecardDetail;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\TimecardDetail;
use Illuminate\Database\Eloquent\Model;

class TimecardDetailMigrator extends BaseMigrator
{
    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
    }

    public function getModelType(): string
    {
        return 'timecard_detail';
    }

    public function getDisplayName(): string
    {
        return 'Timecard Details';
    }

    public function getDependencies(): array
    {
        return ['timecard'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyTimecardDetail::class;
    }

    protected function getOwletModelClass(): string
    {
        return TimecardDetail::class;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy timecard_id to Owlet timecard_id
        $owletTimecardId = $this->lookupOwletId('timecard', $legacyRecord->timecard_id);
        if (! $owletTimecardId) {
            // Timecard might not be migrated (e.g., belongs to a customer)
            return null;
        }

        // Map legacy type to Owlet type
        $type = $this->mapType($legacyRecord->type);

        return [
            'timecard_id' => $owletTimecardId,
            'type' => $type,
            'start_date' => $legacyRecord->start_date,
            'end_date' => $legacyRecord->end_date,
            'hours' => $legacyRecord->hours ?? 0,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
        ];
    }

    /**
     * Map legacy type to Owlet type.
     */
    protected function mapType(?string $legacyType): string
    {
        // Legacy types are typically: 'WORK', 'BREAK', etc.
        return match (strtoupper($legacyType ?? '')) {
            'BREAK', 'LUNCH', 'REST' => TimecardDetail::TYPE_BREAK,
            default => TimecardDetail::TYPE_WORK, // Default to work
        };
    }
}

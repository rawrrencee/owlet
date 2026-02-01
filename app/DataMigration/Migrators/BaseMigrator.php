<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Contracts\MigratorInterface;
use App\DataMigration\Models\MigrationLog;
use App\DataMigration\Models\MigrationRun;
use App\DataMigration\Services\MigrationService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseMigrator implements MigratorInterface
{
    protected MigrationService $migrationService;

    /**
     * Cache for FK lookups: [modelType => [legacyId => owletId]]
     */
    protected array $fkCache = [];

    public function __construct(MigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
    }

    /**
     * Get the legacy Eloquent model class.
     */
    abstract protected function getLegacyModelClass(): string;

    /**
     * Get the Owlet Eloquent model class.
     */
    abstract protected function getOwletModelClass(): string;

    /**
     * Transform a legacy record to Owlet format.
     *
     * @return array<string, mixed>|null Return null to skip the record
     */
    abstract protected function transformRecord(Model $legacyRecord): ?array;

    /**
     * Get the query for fetching legacy records.
     */
    protected function getLegacyQuery()
    {
        return $this->getLegacyModelClass()::query();
    }

    /**
     * Get count of records in legacy database.
     */
    public function getLegacyCount(): int
    {
        return $this->getLegacyQuery()->count();
    }

    /**
     * Get count of successfully migrated records.
     */
    public function getMigratedCount(): int
    {
        return MigrationLog::where('model_type', $this->getModelType())
            ->where('status', MigrationLog::STATUS_SUCCESS)
            ->count();
    }

    /**
     * Get count of failed migrations.
     */
    public function getFailedCount(): int
    {
        return MigrationLog::where('model_type', $this->getModelType())
            ->where('status', MigrationLog::STATUS_FAILED)
            ->count();
    }

    /**
     * Get count of skipped migrations.
     */
    public function getSkippedCount(): int
    {
        return MigrationLog::where('model_type', $this->getModelType())
            ->where('status', MigrationLog::STATUS_SKIPPED)
            ->count();
    }

    /**
     * Check if all dependencies have been fully migrated.
     * Considers both migrated and skipped records as "processed".
     */
    public function areDependenciesMet(): bool
    {
        foreach ($this->getDependencies() as $dependency) {
            $migrator = $this->migrationService->getMigrator($dependency);
            if (! $migrator) {
                return false;
            }

            $legacyCount = $migrator->getLegacyCount();
            $processedCount = $migrator->getMigratedCount() + $migrator->getSkippedCount();

            if ($processedCount < $legacyCount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run the migration for pending records.
     *
     * @param int $batchSize Number of records per database chunk
     * @param int|null $limit Maximum records to process in this run (null = all)
     */
    public function migrate(int $batchSize = 100, ?int $limit = null, ?callable $progressCallback = null): MigrationRun
    {
        $run = MigrationRun::create([
            'model_type' => $this->getModelType(),
            'started_at' => now(),
            'status' => MigrationRun::STATUS_RUNNING,
            'total_records' => $this->getLegacyCount(),
            'initiated_by' => auth()->id(),
        ]);

        try {
            $this->processMigration($run, $batchSize, $limit, $progressCallback, false);
            $run->markCompleted();
        } catch (Exception $e) {
            Log::error("Migration failed for {$this->getModelType()}: ".$e->getMessage(), [
                'run_id' => $run->id,
                'exception' => $e,
            ]);
            $run->markFailed($e->getMessage());
        }

        return $run->fresh();
    }

    /**
     * Retry only failed records.
     *
     * @param int|null $limit Maximum records to process in this run (null = all)
     */
    public function retryFailed(?int $limit = null, ?callable $progressCallback = null): MigrationRun
    {
        $failedCount = $this->getFailedCount();

        $run = MigrationRun::create([
            'model_type' => $this->getModelType(),
            'started_at' => now(),
            'status' => MigrationRun::STATUS_RUNNING,
            'total_records' => $failedCount,
            'initiated_by' => auth()->id(),
        ]);

        try {
            $this->processMigration($run, 100, $limit, $progressCallback, true);
            $run->markCompleted();
        } catch (Exception $e) {
            Log::error("Retry migration failed for {$this->getModelType()}: ".$e->getMessage(), [
                'run_id' => $run->id,
                'exception' => $e,
            ]);
            $run->markFailed($e->getMessage());
        }

        return $run->fresh();
    }

    /**
     * Process migration for records.
     *
     * @param int|null $limit Maximum records to process (null = all)
     */
    protected function processMigration(
        MigrationRun $run,
        int $batchSize,
        ?int $limit,
        ?callable $progressCallback,
        bool $retryOnly
    ): void {
        $processed = 0;

        if ($retryOnly) {
            // Get failed legacy IDs
            $failedIds = MigrationLog::where('model_type', $this->getModelType())
                ->where('status', MigrationLog::STATUS_FAILED)
                ->pluck('legacy_id')
                ->toArray();

            $query = $this->getLegacyQuery()->whereIn('id', $failedIds);
        } else {
            // Exclude already successfully migrated records
            $migratedIds = MigrationLog::where('model_type', $this->getModelType())
                ->where('status', MigrationLog::STATUS_SUCCESS)
                ->pluck('legacy_id')
                ->toArray();

            $query = $this->getLegacyQuery();
            if (! empty($migratedIds)) {
                $query->whereNotIn('id', $migratedIds);
            }
        }

        // Apply limit directly to query for efficiency
        if ($limit !== null) {
            $query->orderBy('id')->limit($limit);
            $records = $query->get();

            foreach ($records as $legacyRecord) {
                $this->migrateRecord($run, $legacyRecord);
                $processed++;

                if ($progressCallback) {
                    $progressCallback($processed, $run->total_records);
                }
            }
        } else {
            $query->orderBy('id')->chunk($batchSize, function ($records) use ($run, &$processed, $progressCallback) {
                foreach ($records as $legacyRecord) {
                    $this->migrateRecord($run, $legacyRecord);
                    $processed++;

                    if ($progressCallback) {
                        $progressCallback($processed, $run->total_records);
                    }
                }
            });
        }
    }

    /**
     * Migrate a single record.
     */
    protected function migrateRecord(MigrationRun $run, Model $legacyRecord): void
    {
        $log = MigrationLog::findOrCreateForLegacy(
            $this->getModelType(),
            $legacyRecord->id,
            $run->id
        );

        // Update log to associate with current run
        $log->update(['migration_run_id' => $run->id]);

        try {
            $data = $this->transformRecord($legacyRecord);

            if ($data === null) {
                $log->markSkipped('Skipped by transformation logic');
                $run->incrementSkipped();

                return;
            }

            DB::transaction(function () use ($log, $run, $data, $legacyRecord) {
                $owletModel = $this->createOwletRecord($data, $legacyRecord);
                $log->markSuccess($owletModel->id, $this->getLogMetadata($legacyRecord, $owletModel));
                $run->incrementMigrated();
            });
        } catch (Exception $e) {
            Log::warning("Failed to migrate {$this->getModelType()} #{$legacyRecord->id}: ".$e->getMessage());
            $log->markFailed($e->getMessage());
            $run->incrementFailed();
        }
    }

    /**
     * Create the Owlet record from transformed data.
     */
    protected function createOwletRecord(array $data, Model $legacyRecord): Model
    {
        $owletModelClass = $this->getOwletModelClass();

        return $owletModelClass::create($data);
    }

    /**
     * Get metadata to store in the migration log.
     */
    protected function getLogMetadata(Model $legacyRecord, Model $owletModel): ?array
    {
        return null;
    }

    /**
     * Verify migrated data integrity.
     *
     * @return array{valid: bool, errors: array<string>, checked: int}
     */
    public function verify(): array
    {
        $errors = [];
        $checked = 0;

        // Basic count verification
        $legacyCount = $this->getLegacyCount();
        $migratedCount = $this->getMigratedCount();
        $failedCount = $this->getFailedCount();
        $skippedCount = $this->getSkippedCount();

        $totalProcessed = $migratedCount + $failedCount + $skippedCount;

        if ($totalProcessed < $legacyCount) {
            $unprocessed = $legacyCount - $totalProcessed;
            $errors[] = "{$unprocessed} records have not been processed yet";
        }

        if ($failedCount > 0) {
            $errors[] = "{$failedCount} records failed to migrate";
        }

        // Sample verification - check random records
        $sampleLogs = MigrationLog::where('model_type', $this->getModelType())
            ->where('status', MigrationLog::STATUS_SUCCESS)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        foreach ($sampleLogs as $log) {
            $checked++;
            $owletModel = $this->getOwletModelClass()::find($log->owlet_id);

            if (! $owletModel) {
                $errors[] = "Owlet record #{$log->owlet_id} not found for legacy #{$log->legacy_id}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'checked' => $checked,
        ];
    }

    /**
     * Look up the Owlet ID for a legacy record that has been migrated.
     * Uses caching to avoid repeated database queries.
     */
    protected function lookupOwletId(string $modelType, int $legacyId): ?int
    {
        // Load cache for this model type if not already loaded
        if (! isset($this->fkCache[$modelType])) {
            $this->fkCache[$modelType] = MigrationLog::where('model_type', $modelType)
                ->where('status', MigrationLog::STATUS_SUCCESS)
                ->pluck('owlet_id', 'legacy_id')
                ->toArray();
        }

        return $this->fkCache[$modelType][$legacyId] ?? null;
    }
}

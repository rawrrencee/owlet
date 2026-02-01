<?php

namespace App\DataMigration\Contracts;

use App\DataMigration\Models\MigrationRun;

interface MigratorInterface
{
    /**
     * Get the model type identifier (e.g., 'company', 'employee').
     */
    public function getModelType(): string;

    /**
     * Get the display name for this migrator.
     */
    public function getDisplayName(): string;

    /**
     * Get array of model types that must be migrated before this one.
     *
     * @return array<string>
     */
    public function getDependencies(): array;

    /**
     * Get total count of records in legacy database.
     */
    public function getLegacyCount(): int;

    /**
     * Get count of successfully migrated records.
     */
    public function getMigratedCount(): int;

    /**
     * Get count of failed migrations.
     */
    public function getFailedCount(): int;

    /**
     * Get count of skipped migrations.
     */
    public function getSkippedCount(): int;

    /**
     * Run the migration for pending records.
     *
     * @param  int  $batchSize  Number of records to process at a time
     * @param  int|null  $limit  Maximum records to process in this run (null = all)
     * @param  callable|null  $progressCallback  Called with (int $processed, int $total)
     */
    public function migrate(int $batchSize = 100, ?int $limit = null, ?callable $progressCallback = null): MigrationRun;

    /**
     * Retry only failed records.
     *
     * @param  int|null  $limit  Maximum records to process in this run (null = all)
     * @param  callable|null  $progressCallback  Called with (int $processed, int $total)
     */
    public function retryFailed(?int $limit = null, ?callable $progressCallback = null): MigrationRun;

    /**
     * Verify migrated data integrity.
     *
     * @return array{valid: bool, errors: array<string>, checked: int}
     */
    public function verify(): array;

    /**
     * Check if all dependencies have been fully migrated.
     */
    public function areDependenciesMet(): bool;
}

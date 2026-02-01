<?php

namespace App\DataMigration\Http\Controllers;

use App\DataMigration\Http\Resources\MigrationLogResource;
use App\DataMigration\Http\Resources\MigrationRunResource;
use App\DataMigration\Services\MigrationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DataMigrationController extends Controller
{
    public function __construct(
        protected MigrationService $migrationService
    ) {}

    /**
     * Display the migration dashboard.
     */
    public function index()
    {
        $status = $this->migrationService->getAllStatus();
        $connectionTest = $this->migrationService->testLegacyConnection();

        return Inertia::render('Admin/DataMigration/Index', [
            'status' => $status,
            'connectionTest' => $connectionTest,
        ]);
    }

    /**
     * Display migration details for a specific model type.
     */
    public function show(string $modelType)
    {
        $migrator = $this->migrationService->getMigrator($modelType);

        if (! $migrator) {
            abort(404, "Unknown model type: {$modelType}");
        }

        $recentRuns = $this->migrationService->getRecentRuns($modelType);
        $failedLogs = $this->migrationService->getFailedLogs($modelType);

        // Get dependency status
        $dependencyStatus = [];
        foreach ($migrator->getDependencies() as $dep) {
            $depMigrator = $this->migrationService->getMigrator($dep);
            if ($depMigrator) {
                $processedCount = $depMigrator->getMigratedCount() + $depMigrator->getSkippedCount();
                $dependencyStatus[] = [
                    'type' => $dep,
                    'display_name' => $depMigrator->getDisplayName(),
                    'legacy_count' => $depMigrator->getLegacyCount(),
                    'migrated_count' => $depMigrator->getMigratedCount(),
                    'is_complete' => $processedCount >= $depMigrator->getLegacyCount(),
                ];
            }
        }

        return Inertia::render('Admin/DataMigration/Show', [
            'modelType' => $modelType,
            'displayName' => $migrator->getDisplayName(),
            'legacyCount' => $migrator->getLegacyCount(),
            'migratedCount' => $migrator->getMigratedCount(),
            'failedCount' => $migrator->getFailedCount(),
            'skippedCount' => $migrator->getSkippedCount(),
            'dependenciesMet' => $migrator->areDependenciesMet(),
            'dependencies' => $dependencyStatus,
            'recentRuns' => MigrationRunResource::collection($recentRuns),
            'failedLogs' => MigrationLogResource::collection($failedLogs),
        ]);
    }

    /**
     * Run migration for a specific model type.
     */
    public function migrate(Request $request, string $modelType)
    {
        // Extend time limit for migrations
        set_time_limit(45);

        $migrator = $this->migrationService->getMigrator($modelType);

        if (! $migrator) {
            abort(404, "Unknown model type: {$modelType}");
        }

        if (! $migrator->areDependenciesMet()) {
            return back()->withErrors([
                'migration' => 'Cannot migrate: dependencies not met. Please migrate dependencies first.',
            ]);
        }

        $batchSize = $request->input('batch_size', 100);
        $limit = $request->input('limit', 500); // Process up to 500 records per request

        // Run migration with limit
        $run = $migrator->migrate($batchSize, $limit);

        // Check if there are more records to process
        $remaining = $migrator->getLegacyCount() - $migrator->getMigratedCount() - $migrator->getSkippedCount();

        $message = $run->isCompleted()
            ? "Batch completed: {$run->migrated_count} migrated, {$run->failed_count} failed, {$run->skipped_count} skipped."
            : "Migration failed: {$run->error_message}";

        if ($remaining > 0 && $run->isCompleted()) {
            $message .= " {$remaining} records remaining.";
        }

        return back()->with('message', $message);
    }

    /**
     * Verify migrated data for a specific model type.
     */
    public function verify(string $modelType)
    {
        // Extend time limit for verification
        set_time_limit(45);

        $migrator = $this->migrationService->getMigrator($modelType);

        if (! $migrator) {
            abort(404, "Unknown model type: {$modelType}");
        }

        $result = $migrator->verify();

        $message = $result['valid']
            ? "Verification passed! Checked {$result['checked']} records."
            : 'Verification found issues: '.implode('; ', $result['errors']);

        return back()->with('message', $message);
    }

    /**
     * Retry failed migrations for a specific model type.
     */
    public function retryFailed(string $modelType)
    {
        // Extend time limit for retries
        set_time_limit(45);

        $migrator = $this->migrationService->getMigrator($modelType);

        if (! $migrator) {
            abort(404, "Unknown model type: {$modelType}");
        }

        $run = $migrator->retryFailed();

        $message = $run->isCompleted()
            ? "Retry completed: {$run->migrated_count} migrated, {$run->failed_count} still failed."
            : "Retry failed: {$run->error_message}";

        return back()->with('message', $message);
    }

    /**
     * Test the legacy database connection.
     */
    public function testConnection()
    {
        $result = $this->migrationService->testLegacyConnection();

        return back()->with('message', $result['message']);
    }
}

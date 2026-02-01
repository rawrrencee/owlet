<?php

namespace App\DataMigration\Services;

use App\DataMigration\Contracts\MigratorInterface;
use App\DataMigration\Migrators\BrandMigrator;
use App\DataMigration\Migrators\CategoryMigrator;
use App\DataMigration\Migrators\CompanyMigrator;
use App\DataMigration\Migrators\CustomerMigrator;
use App\DataMigration\Migrators\DesignationMigrator;
use App\DataMigration\Migrators\EmployeeCompanyMigrator;
use App\DataMigration\Migrators\EmployeeContractMigrator;
use App\DataMigration\Migrators\EmployeeHierarchyMigrator;
use App\DataMigration\Migrators\EmployeeInsuranceMigrator;
use App\DataMigration\Migrators\EmployeeMigrator;
use App\DataMigration\Migrators\EmployeeStoreMigrator;
use App\DataMigration\Migrators\ProductMigrator;
use App\DataMigration\Migrators\ProductStoreMigrator;
use App\DataMigration\Migrators\StoreMigrator;
use App\DataMigration\Migrators\SubcategoryMigrator;
use App\DataMigration\Migrators\SupplierMigrator;
use App\DataMigration\Migrators\TimecardDetailMigrator;
use App\DataMigration\Migrators\TimecardMigrator;
use App\DataMigration\Models\MigrationLog;
use App\DataMigration\Models\MigrationRun;
use Illuminate\Support\Facades\DB;

class MigrationService
{
    protected ImageMigrationService $imageMigrationService;

    /**
     * @var array<string, class-string<MigratorInterface>>
     */
    protected array $migratorClasses = [
        'designation' => DesignationMigrator::class,
        'company' => CompanyMigrator::class,
        'store' => StoreMigrator::class,
        'category' => CategoryMigrator::class,
        'subcategory' => SubcategoryMigrator::class,
        'brand' => BrandMigrator::class,
        'supplier' => SupplierMigrator::class,
        'employee' => EmployeeMigrator::class,
        'employee_company' => EmployeeCompanyMigrator::class,
        'employee_store' => EmployeeStoreMigrator::class,
        'employee_contract' => EmployeeContractMigrator::class,
        'employee_insurance' => EmployeeInsuranceMigrator::class,
        'employee_hierarchy' => EmployeeHierarchyMigrator::class,
        'customer' => CustomerMigrator::class,
        'product' => ProductMigrator::class,
        'product_store' => ProductStoreMigrator::class,
        'timecard' => TimecardMigrator::class,
        'timecard_detail' => TimecardDetailMigrator::class,
    ];

    /**
     * @var array<string, MigratorInterface>
     */
    protected array $migratorInstances = [];

    public function __construct(ImageMigrationService $imageMigrationService)
    {
        $this->imageMigrationService = $imageMigrationService;
    }

    /**
     * Get all available migrator types.
     *
     * @return array<string>
     */
    public function getMigratorTypes(): array
    {
        return array_keys($this->migratorClasses);
    }

    /**
     * Get a migrator instance by type.
     */
    public function getMigrator(string $type): ?MigratorInterface
    {
        if (! isset($this->migratorClasses[$type])) {
            return null;
        }

        if (! isset($this->migratorInstances[$type])) {
            $class = $this->migratorClasses[$type];
            $this->migratorInstances[$type] = new $class($this, $this->imageMigrationService);
        }

        return $this->migratorInstances[$type];
    }

    /**
     * Get status summary for all migrators.
     *
     * @return array<array{
     *     type: string,
     *     display_name: string,
     *     legacy_count: int,
     *     migrated_count: int,
     *     failed_count: int,
     *     skipped_count: int,
     *     dependencies: array<string>,
     *     dependencies_met: bool
     * }>
     */
    public function getAllStatus(): array
    {
        $status = [];

        foreach ($this->getMigratorTypes() as $type) {
            $migrator = $this->getMigrator($type);
            if (! $migrator) {
                continue;
            }

            $status[] = [
                'type' => $type,
                'display_name' => $migrator->getDisplayName(),
                'legacy_count' => $migrator->getLegacyCount(),
                'migrated_count' => $migrator->getMigratedCount(),
                'failed_count' => $migrator->getFailedCount(),
                'skipped_count' => $migrator->getSkippedCount(),
                'dependencies' => $migrator->getDependencies(),
                'dependencies_met' => $migrator->areDependenciesMet(),
            ];
        }

        return $status;
    }

    /**
     * Get recent migration runs for a model type.
     *
     * @return \Illuminate\Database\Eloquent\Collection<MigrationRun>
     */
    public function getRecentRuns(string $modelType, int $limit = 10)
    {
        return MigrationRun::where('model_type', $modelType)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed logs for a model type.
     *
     * @return \Illuminate\Database\Eloquent\Collection<MigrationLog>
     */
    public function getFailedLogs(string $modelType, int $limit = 50)
    {
        return MigrationLog::where('model_type', $modelType)
            ->where('status', MigrationLog::STATUS_FAILED)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Test the legacy database connection.
     *
     * @return array{success: bool, message: string, tables?: array<string>}
     */
    public function testLegacyConnection(): array
    {
        try {
            $tables = DB::connection('legacy')->select('SHOW TABLES');

            return [
                'success' => true,
                'message' => 'Successfully connected to legacy database',
                'tables' => array_map(fn ($t) => array_values((array) $t)[0], $tables),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get the Owlet ID for a migrated legacy record.
     */
    public function lookupOwletId(string $modelType, int $legacyId): ?int
    {
        $log = MigrationLog::where('model_type', $modelType)
            ->where('legacy_id', $legacyId)
            ->where('status', MigrationLog::STATUS_SUCCESS)
            ->first();

        return $log?->owlet_id;
    }
}

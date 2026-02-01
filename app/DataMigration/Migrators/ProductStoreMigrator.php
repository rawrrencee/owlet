<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\ItemStore as LegacyItemStore;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Currency;
use App\Models\ProductStore;
use App\Models\ProductStorePrice;
use Illuminate\Database\Eloquent\Model;

class ProductStoreMigrator extends BaseMigrator
{
    // Legacy store ID for SEIBU TRX (Malaysia) which uses MYR
    private const LEGACY_SEIBU_TRX_STORE_ID = 16;

    protected ?int $sgdCurrencyId = null;

    protected ?int $myrCurrencyId = null;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
    }

    public function getModelType(): string
    {
        return 'product_store';
    }

    public function getDisplayName(): string
    {
        return 'Product-Store Assignments';
    }

    public function getDependencies(): array
    {
        return ['product', 'store'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyItemStore::class;
    }

    protected function getOwletModelClass(): string
    {
        return ProductStore::class;
    }

    protected function getSgdCurrencyId(): int
    {
        if ($this->sgdCurrencyId === null) {
            $currency = Currency::where('code', 'SGD')->first();
            if (! $currency) {
                throw new \Exception('SGD currency not found in Owlet. Please create it before running migration.');
            }
            $this->sgdCurrencyId = $currency->id;
        }

        return $this->sgdCurrencyId;
    }

    protected function getMyrCurrencyId(): int
    {
        if ($this->myrCurrencyId === null) {
            $currency = Currency::where('code', 'MYR')->first();
            if (! $currency) {
                throw new \Exception('MYR currency not found in Owlet. Please create it before running migration for Malaysian stores.');
            }
            $this->myrCurrencyId = $currency->id;
        }

        return $this->myrCurrencyId;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map legacy item_id to Owlet product_id
        $owletProductId = $this->lookupOwletId('product', $legacyRecord->item_id);
        if (! $owletProductId) {
            throw new \Exception("Product (Item #{$legacyRecord->item_id}) not migrated yet");
        }

        // Map legacy store_id to Owlet store_id
        $owletStoreId = $this->lookupOwletId('store', $legacyRecord->store_id);
        if (! $owletStoreId) {
            throw new \Exception("Store #{$legacyRecord->store_id} not migrated yet");
        }

        return [
            'product_id' => $owletProductId,
            'store_id' => $owletStoreId,
            'quantity' => $legacyRecord->quantity ?? 0,
            'is_active' => (bool) $legacyRecord->active,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            // Store extra data for price creation
            '_legacy_cost_price' => $legacyRecord->cost_price,
            '_legacy_unit_price' => $legacyRecord->unit_price,
            '_legacy_store_id' => $legacyRecord->store_id,
        ];
    }

    protected function createOwletRecord(array $data, Model $legacyRecord): Model
    {
        // Extract pricing data before creating product store
        $costPrice = $data['_legacy_cost_price'] ?? null;
        $unitPrice = $data['_legacy_unit_price'] ?? null;
        $legacyStoreId = $data['_legacy_store_id'];
        unset($data['_legacy_cost_price'], $data['_legacy_unit_price'], $data['_legacy_store_id']);

        // Create product store assignment
        $productStore = ProductStore::create($data);

        // Create store-specific price
        // Use MYR for SEIBU TRX store, SGD for all others
        if ($costPrice !== null || $unitPrice !== null) {
            $currencyId = $legacyStoreId === self::LEGACY_SEIBU_TRX_STORE_ID
                ? $this->getMyrCurrencyId()
                : $this->getSgdCurrencyId();

            ProductStorePrice::create([
                'product_store_id' => $productStore->id,
                'currency_id' => $currencyId,
                'cost_price' => $costPrice,
                'unit_price' => $unitPrice,
            ]);
        }

        return $productStore;
    }
}

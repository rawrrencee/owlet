<?php

namespace App\DataMigration\Migrators;

use App\DataMigration\Models\Legacy\Item as LegacyItem;
use App\DataMigration\Services\ImageMigrationService;
use App\DataMigration\Services\MigrationService;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductMigrator extends BaseMigrator
{
    protected ImageMigrationService $imageMigrationService;

    protected ?int $sgdCurrencyId = null;

    public function __construct(MigrationService $migrationService, ImageMigrationService $imageMigrationService)
    {
        parent::__construct($migrationService);
        $this->imageMigrationService = $imageMigrationService;
    }

    public function getModelType(): string
    {
        return 'product';
    }

    public function getDisplayName(): string
    {
        return 'Products';
    }

    public function getDependencies(): array
    {
        return ['brand', 'category', 'subcategory', 'supplier'];
    }

    protected function getLegacyModelClass(): string
    {
        return LegacyItem::class;
    }

    protected function getOwletModelClass(): string
    {
        return Product::class;
    }

    protected function getSgdCurrencyId(): int
    {
        if ($this->sgdCurrencyId === null) {
            $currency = Currency::where('code', 'SGD')->first();
            if (! $currency) {
                throw new \Exception('SGD currency not found in Owlet. Please create it before running product migration.');
            }
            $this->sgdCurrencyId = $currency->id;
        }

        return $this->sgdCurrencyId;
    }

    protected function transformRecord(Model $legacyRecord): ?array
    {
        // Map FK references
        $owletBrandId = $legacyRecord->brand_id ? $this->lookupOwletId('brand', $legacyRecord->brand_id) : null;
        $owletCategoryId = $legacyRecord->category_id ? $this->lookupOwletId('category', $legacyRecord->category_id) : null;
        $owletSubcategoryId = $legacyRecord->subcategory_id ? $this->lookupOwletId('subcategory', $legacyRecord->subcategory_id) : null;
        $owletSupplierId = $legacyRecord->supplier_id ? $this->lookupOwletId('supplier', $legacyRecord->supplier_id) : null;

        $data = [
            'brand_id' => $owletBrandId,
            'category_id' => $owletCategoryId,
            'subcategory_id' => $owletSubcategoryId,
            'supplier_id' => $owletSupplierId,
            'product_name' => $legacyRecord->item_name,
            'product_number' => strtoupper($legacyRecord->item_number ?? ''),
            'barcode' => null, // Legacy doesn't have barcode field
            'supplier_number' => $legacyRecord->supplier_number,
            'description' => $legacyRecord->description,
            'cost_price_remarks' => $legacyRecord->cost_price_remarks,
            'is_active' => (bool) $legacyRecord->active,
            'created_at' => $legacyRecord->created_at,
            'updated_at' => $legacyRecord->updated_at,
            'deleted_at' => $legacyRecord->deleted_at,
        ];

        // Skip image migration for speed - images can be migrated separately if needed
        // Store the legacy URL in metadata for reference
        if ($legacyRecord->img_url) {
            $data['_legacy_img_url'] = $legacyRecord->img_url;
        }

        // Store pricing data temporarily - will be used in createOwletRecord
        $data['_legacy_cost_price'] = $legacyRecord->cost_price;
        $data['_legacy_unit_price'] = $legacyRecord->unit_price;

        return $data;
    }

    protected function createOwletRecord(array $data, Model $legacyRecord): Model
    {
        // Extract temporary data before creating product
        $costPrice = $data['_legacy_cost_price'] ?? null;
        $unitPrice = $data['_legacy_unit_price'] ?? null;
        unset($data['_legacy_cost_price'], $data['_legacy_unit_price'], $data['_legacy_img_url']);

        // Create product
        $product = Product::create($data);

        // Create base price in SGD
        if ($costPrice !== null || $unitPrice !== null) {
            ProductPrice::create([
                'product_id' => $product->id,
                'currency_id' => $this->getSgdCurrencyId(),
                'cost_price' => $costPrice ?? 0,
                'unit_price' => $unitPrice ?? 0,
            ]);
        }

        return $product;
    }

    protected function getLogMetadata(Model $legacyRecord, Model $owletModel): ?array
    {
        return [
            'legacy_img_url' => $legacyRecord->img_url,
        ];
    }
}

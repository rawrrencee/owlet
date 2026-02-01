<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id',
        'quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the store-specific prices for this product-store assignment.
     */
    public function storePrices(): HasMany
    {
        return $this->hasMany(ProductStorePrice::class);
    }

    /**
     * Get the store-specific price for a currency, with fallback to base product price.
     */
    public function getEffectivePriceForCurrency(int $currencyId): ?ProductStorePrice
    {
        return $this->storePrices->firstWhere('currency_id', $currencyId);
    }

    /**
     * Get the effective cost price for a currency.
     * Returns store-specific price if set, otherwise falls back to base product price.
     */
    public function getEffectiveCostPrice(int $currencyId): ?string
    {
        $storePrice = $this->getEffectivePriceForCurrency($currencyId);

        if ($storePrice && $storePrice->cost_price !== null) {
            return $storePrice->cost_price;
        }

        // Fall back to base product price
        return $this->product->getPriceForCurrency($currencyId)?->cost_price;
    }

    /**
     * Get the effective unit price for a currency.
     * Returns store-specific price if set, otherwise falls back to base product price.
     */
    public function getEffectiveUnitPrice(int $currencyId): ?string
    {
        $storePrice = $this->getEffectivePriceForCurrency($currencyId);

        if ($storePrice && $storePrice->unit_price !== null) {
            return $storePrice->unit_price;
        }

        // Fall back to base product price
        return $this->product->getPriceForCurrency($currencyId)?->unit_price;
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\TouchesProductAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStorePrice extends Model
{
    use HasFactory, TouchesProductAuditTrail;

    /**
     * Get the Product model for audit trail updates.
     */
    protected function getProductForAuditTrail(): ?Product
    {
        return $this->productStore?->product;
    }

    protected $fillable = [
        'product_store_id',
        'currency_id',
        'cost_price',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }

    public function productStore(): BelongsTo
    {
        return $this->belongsTo(ProductStore::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the effective cost price (with fallback to base product price).
     */
    public function getEffectiveCostPrice(): ?string
    {
        if ($this->cost_price !== null) {
            return $this->cost_price;
        }

        return $this->productStore->product->prices
            ->firstWhere('currency_id', $this->currency_id)?->cost_price;
    }

    /**
     * Get the effective unit price (with fallback to base product price).
     */
    public function getEffectiveUnitPrice(): ?string
    {
        if ($this->unit_price !== null) {
            return $this->unit_price;
        }

        return $this->productStore->product->prices
            ->firstWhere('currency_id', $this->currency_id)?->unit_price;
    }
}

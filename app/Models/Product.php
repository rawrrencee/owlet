<?php

namespace App\Models;

use App\Enums\WeightUnit;
use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasAuditTrail, HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'category_id',
        'subcategory_id',
        'supplier_id',
        'product_name',
        'product_number',
        'barcode',
        'supplier_number',
        'description',
        'cost_price_remarks',
        'image_path',
        'image_filename',
        'image_mime_type',
        'weight',
        'weight_unit',
        'is_active',
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'weight_unit' => WeightUnit::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mutator to ensure product_number is always uppercase and trimmed.
     */
    public function setProductNumberAttribute(string $value): void
    {
        $this->attributes['product_number'] = strtoupper(trim($value));
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the base prices for this product (per currency).
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Get the product-store assignments.
     */
    public function productStores(): HasMany
    {
        return $this->hasMany(ProductStore::class);
    }

    /**
     * Get the stores this product is assigned to.
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'product_stores')
            ->withPivot(['quantity', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get active stores this product is assigned to.
     */
    public function activeStores(): BelongsToMany
    {
        return $this->stores()->wherePivot('is_active', true);
    }

    /**
     * Get the tags for this product.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Sync tags by name (creates tags if they don't exist).
     *
     * @param  array<string>  $tagNames
     */
    public function syncTagsByName(array $tagNames): void
    {
        $tags = Tag::findOrCreateByNames($tagNames);
        $this->tags()->sync($tags->pluck('id'));
    }

    /**
     * Get the base price for a specific currency.
     */
    public function getPriceForCurrency(int $currencyId): ?ProductPrice
    {
        return $this->prices->firstWhere('currency_id', $currencyId);
    }

    /**
     * Scope to search products by name, number, or barcode.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('product_name', 'like', "%{$search}%")
                ->orWhere('product_number', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter by active status.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by tag name.
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereHas('tags', function ($q) use ($tag) {
            $q->where('name', strtolower(trim($tag)));
        });
    }

    /**
     * Scope to filter by any of the given tags.
     *
     * @param  array<string>  $tags
     */
    public function scopeWithAnyTags($query, array $tags)
    {
        $normalizedTags = collect($tags)->map(fn ($tag) => strtolower(trim($tag)))->toArray();

        return $query->whereHas('tags', function ($q) use ($normalizedTags) {
            $q->whereIn('name', $normalizedTags);
        });
    }

    /**
     * Scope to filter by all of the given tags.
     *
     * @param  array<string>  $tags
     */
    public function scopeWithAllTags($query, array $tags)
    {
        $normalizedTags = collect($tags)->map(fn ($tag) => strtolower(trim($tag)))->toArray();

        foreach ($normalizedTags as $tag) {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', $tag);
            });
        }

        return $query;
    }
}

<?php

namespace App\Models;

use App\Enums\BundleMode;
use App\Enums\DiscountType;
use App\Enums\OfferStatus;
use App\Enums\OfferType;
use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'discount_type',
        'discount_percentage',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'is_combinable',
        'priority',
        'apply_to_all_stores',
        'category_id',
        'brand_id',
        'bundle_mode',
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => OfferType::class,
            'discount_type' => DiscountType::class,
            'status' => OfferStatus::class,
            'bundle_mode' => BundleMode::class,
            'discount_percentage' => 'decimal:2',
            'is_combinable' => 'boolean',
            'apply_to_all_stores' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    // Relationships

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'offer_stores');
    }

    public function amounts(): HasMany
    {
        return $this->hasMany(OfferAmount::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(OfferProduct::class);
    }

    public function bundles(): HasMany
    {
        return $this->hasMany(OfferBundle::class);
    }

    public function minimumSpends(): HasMany
    {
        return $this->hasMany(OfferMinimumSpend::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OfferStatus::ACTIVE);
    }

    public function scopeForStore(Builder $query, ?int $storeId = null): Builder
    {
        if ($storeId === null) {
            return $query->where('apply_to_all_stores', true);
        }

        return $query->where(function ($q) use ($storeId) {
            $q->where('apply_to_all_stores', true)
                ->orWhereHas('stores', fn ($sq) => $sq->where('store_id', $storeId));
        });
    }

    public function scopeOfType(Builder $query, OfferType|string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeProductLevel(Builder $query): Builder
    {
        return $query->whereIn('type', [
            OfferType::PRODUCT,
            OfferType::CATEGORY,
            OfferType::BRAND,
        ]);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        });
    }

    // Helpers

    public function isCurrentlyActive(): bool
    {
        if ($this->status !== OfferStatus::ACTIVE) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function appliesToStore(int $storeId): bool
    {
        if ($this->apply_to_all_stores) {
            return true;
        }

        return $this->stores->contains('id', $storeId);
    }

    public function getAmountForCurrency(int $currencyId): ?OfferAmount
    {
        return $this->amounts->firstWhere('currency_id', $currencyId);
    }
}

<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\OfferStatus;
use App\Enums\OfferType;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OfferService
{
    public function create(array $data, mixed $user): Offer
    {
        return DB::transaction(function () use ($data, $user) {
            $offer = Offer::create([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'type' => $data['type'],
                'discount_type' => $data['discount_type'],
                'discount_percentage' => $data['discount_percentage'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => OfferStatus::DRAFT,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_combinable' => $data['is_combinable'] ?? false,
                'priority' => $data['priority'] ?? 0,
                'apply_to_all_stores' => $data['apply_to_all_stores'] ?? true,
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'bundle_mode' => $data['bundle_mode'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncRelatedRecords($offer, $data);

            return $offer;
        });
    }

    public function update(Offer $offer, array $data, mixed $user): Offer
    {
        return DB::transaction(function () use ($offer, $data) {
            $offer->update([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'type' => $data['type'],
                'discount_type' => $data['discount_type'],
                'discount_percentage' => $data['discount_percentage'] ?? null,
                'description' => $data['description'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_combinable' => $data['is_combinable'] ?? false,
                'priority' => $data['priority'] ?? 0,
                'apply_to_all_stores' => $data['apply_to_all_stores'] ?? true,
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'bundle_mode' => $data['bundle_mode'] ?? null,
            ]);

            $this->syncRelatedRecords($offer, $data);

            return $offer->fresh();
        });
    }

    public function delete(Offer $offer): void
    {
        $offer->delete();
    }

    public function activate(Offer $offer): Offer
    {
        abort_unless(
            in_array($offer->status, [OfferStatus::DRAFT, OfferStatus::DISABLED, OfferStatus::SCHEDULED]),
            422,
            'Only draft, disabled, or scheduled offers can be activated.'
        );

        $offer->update(['status' => OfferStatus::ACTIVE]);

        return $offer;
    }

    public function disable(Offer $offer): Offer
    {
        abort_unless(
            in_array($offer->status, [OfferStatus::ACTIVE, OfferStatus::SCHEDULED]),
            422,
            'Only active or scheduled offers can be disabled.'
        );

        $offer->update(['status' => OfferStatus::DISABLED]);

        return $offer;
    }

    // Resolution methods for POS/Quotation

    /**
     * Load all active offers for a store (called once on POS page load).
     */
    public function getActiveOffersForStore(int $storeId): array
    {
        $offers = Offer::active()
            ->forStore($storeId)
            ->with(['amounts', 'products', 'bundles.product', 'bundles.category', 'bundles.subcategory.category', 'minimumSpends', 'category', 'brand', 'stores'])
            ->orderBy('priority')
            ->get();

        return [
            'product' => $offers->where('type', OfferType::PRODUCT)->values(),
            'category' => $offers->where('type', OfferType::CATEGORY)->values(),
            'brand' => $offers->where('type', OfferType::BRAND)->values(),
            'bundle' => $offers->where('type', OfferType::BUNDLE)->values(),
            'minimum_spend' => $offers->where('type', OfferType::MINIMUM_SPEND)->values(),
        ];
    }

    /**
     * Find best product-level offer for a product being added.
     * Checks product-specific, category, and brand offers.
     */
    public function findBestProductOffer(Product $product, ?int $storeId, int $currencyId, string $unitPrice): ?array
    {
        $offers = Offer::active()
            ->forStore($storeId)
            ->productLevel()
            ->with(['amounts', 'products'])
            ->orderBy('priority')
            ->get();

        $bestOffer = null;
        $bestDiscount = '0';

        foreach ($offers as $offer) {
            if (! $this->offerAppliesToProduct($offer, $product)) {
                continue;
            }

            $discount = $this->calculateProductDiscount($offer, $currencyId, $unitPrice);
            if ($discount !== null && bccomp($discount, $bestDiscount, 4) > 0) {
                $bestDiscount = $discount;
                $bestOffer = [
                    'offer_id' => $offer->id,
                    'offer_name' => $offer->name,
                    'discount_type' => $offer->discount_type->value,
                    'discount_percentage' => $offer->discount_percentage ? (float) $offer->discount_percentage : null,
                    'discount_amount' => $discount,
                    'is_combinable' => $offer->is_combinable,
                ];
            }
        }

        return $bestOffer;
    }

    /**
     * Detect satisfied bundle offers given current cart contents.
     *
     * @param  array  $cartItems  [product_id => ['quantity' => N, 'category_id' => X, 'subcategory_id' => Y]]
     */
    public function findApplicableBundleOffers(array $cartItems, ?int $storeId, int $currencyId): array
    {
        $offers = Offer::active()
            ->forStore($storeId)
            ->ofType(OfferType::BUNDLE)
            ->with(['amounts', 'bundles.product', 'bundles.category', 'bundles.subcategory'])
            ->orderBy('priority')
            ->get();

        $applicable = [];

        foreach ($offers as $offer) {
            $satisfied = true;

            foreach ($offer->bundles as $bundleItem) {
                if ($bundleItem->isProductEntry()) {
                    $cartQty = $cartItems[$bundleItem->product_id]['quantity'] ?? 0;
                    if ($cartQty < $bundleItem->required_quantity) {
                        $satisfied = false;
                        break;
                    }
                } elseif ($bundleItem->isCategoryEntry()) {
                    $totalQty = 0;
                    foreach ($cartItems as $item) {
                        if (($item['category_id'] ?? null) === $bundleItem->category_id) {
                            $totalQty += $item['quantity'] ?? 0;
                        }
                    }
                    if ($totalQty < $bundleItem->required_quantity) {
                        $satisfied = false;
                        break;
                    }
                } elseif ($bundleItem->isSubcategoryEntry()) {
                    $totalQty = 0;
                    foreach ($cartItems as $item) {
                        if (($item['subcategory_id'] ?? null) === $bundleItem->subcategory_id) {
                            $totalQty += $item['quantity'] ?? 0;
                        }
                    }
                    if ($totalQty < $bundleItem->required_quantity) {
                        $satisfied = false;
                        break;
                    }
                }
            }

            if ($satisfied) {
                $applicable[] = [
                    'offer_id' => $offer->id,
                    'offer_name' => $offer->name,
                    'bundle_mode' => $offer->bundle_mode?->value,
                    'discount_type' => $offer->discount_type->value,
                    'discount_percentage' => $offer->discount_percentage,
                    'amount' => $offer->getAmountForCurrency($currencyId),
                    'is_combinable' => $offer->is_combinable,
                    'bundle_items' => $offer->bundles->map(fn ($b) => [
                        'product_id' => $b->product_id,
                        'category_id' => $b->category_id,
                        'subcategory_id' => $b->subcategory_id,
                        'required_quantity' => $b->required_quantity,
                    ])->toArray(),
                ];
            }
        }

        return $applicable;
    }

    /**
     * Find best minimum spend offer for a cart total.
     */
    public function findBestMinimumSpendOffer(string $cartTotal, int $storeId, int $currencyId): ?array
    {
        $offers = Offer::active()
            ->forStore($storeId)
            ->ofType(OfferType::MINIMUM_SPEND)
            ->with(['amounts', 'minimumSpends'])
            ->orderBy('priority')
            ->get();

        $bestOffer = null;
        $bestDiscount = '0';

        foreach ($offers as $offer) {
            $minSpend = $offer->minimumSpends->firstWhere('currency_id', $currencyId);
            if (! $minSpend || bccomp($cartTotal, $minSpend->minimum_amount, 4) < 0) {
                continue;
            }

            $discount = $this->calculateCartDiscount($offer, $currencyId, $cartTotal);
            if ($discount !== null && bccomp($discount, $bestDiscount, 4) > 0) {
                $bestDiscount = $discount;
                $bestOffer = [
                    'offer_id' => $offer->id,
                    'offer_name' => $offer->name,
                    'discount_type' => $offer->discount_type->value,
                    'discount_amount' => $discount,
                    'minimum_amount' => $minSpend->minimum_amount,
                    'is_combinable' => $offer->is_combinable,
                ];
            }
        }

        return $bestOffer;
    }

    /**
     * Update scheduled/active offer statuses.
     */
    public function updateStatuses(): int
    {
        $count = 0;
        $now = now();

        // Scheduled → Active
        $count += Offer::where('status', OfferStatus::SCHEDULED)
            ->where('starts_at', '<=', $now)
            ->update(['status' => OfferStatus::ACTIVE]);

        // Active → Expired
        $count += Offer::where('status', OfferStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->update(['status' => OfferStatus::EXPIRED]);

        return $count;
    }

    // Private helpers

    protected function syncRelatedRecords(Offer $offer, array $data): void
    {
        // Sync stores
        if (! ($data['apply_to_all_stores'] ?? true)) {
            $storeIds = $data['store_ids'] ?? [];
            $offer->stores()->sync($storeIds);
        } else {
            $offer->stores()->detach();
        }

        // Sync amounts (for fixed discount or max discount cap)
        $offer->amounts()->delete();
        if (! empty($data['amounts'])) {
            foreach ($data['amounts'] as $amount) {
                if (! empty($amount['currency_id'])) {
                    $offer->amounts()->create([
                        'currency_id' => $amount['currency_id'],
                        'discount_amount' => $amount['discount_amount'] ?? null,
                        'max_discount_amount' => $amount['max_discount_amount'] ?? null,
                    ]);
                }
            }
        }

        // Sync products (for type=product)
        $offer->products()->delete();
        if ($data['type'] === OfferType::PRODUCT->value && ! empty($data['product_ids'])) {
            foreach ($data['product_ids'] as $productId) {
                $offer->products()->create(['product_id' => $productId]);
            }
        }

        // Sync bundles (for type=bundle)
        $offer->bundles()->delete();
        if ($data['type'] === OfferType::BUNDLE->value && ! empty($data['bundle_items'])) {
            foreach ($data['bundle_items'] as $item) {
                $offer->bundles()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'category_id' => $item['category_id'] ?? null,
                    'subcategory_id' => $item['subcategory_id'] ?? null,
                    'required_quantity' => $item['required_quantity'] ?? 1,
                ]);
            }
        }

        // Sync minimum spends (for type=minimum_spend)
        $offer->minimumSpends()->delete();
        if ($data['type'] === OfferType::MINIMUM_SPEND->value && ! empty($data['minimum_spends'])) {
            foreach ($data['minimum_spends'] as $spend) {
                if (! empty($spend['currency_id']) && ! empty($spend['minimum_amount'])) {
                    $offer->minimumSpends()->create([
                        'currency_id' => $spend['currency_id'],
                        'minimum_amount' => $spend['minimum_amount'],
                    ]);
                }
            }
        }
    }

    protected function offerAppliesToProduct(Offer $offer, Product $product): bool
    {
        return match ($offer->type) {
            OfferType::PRODUCT => $offer->products->contains('product_id', $product->id),
            OfferType::CATEGORY => $offer->category_id === $product->category_id,
            OfferType::BRAND => $offer->brand_id === $product->brand_id,
            default => false,
        };
    }

    protected function calculateProductDiscount(Offer $offer, int $currencyId, string $unitPrice): ?string
    {
        if ($offer->discount_type === DiscountType::PERCENTAGE) {
            $discount = bcmul($unitPrice, bcdiv((string) $offer->discount_percentage, '100', 6), 4);

            // Apply max cap if set
            $amount = $offer->getAmountForCurrency($currencyId);
            if ($amount?->max_discount_amount && bccomp($discount, (string) $amount->max_discount_amount, 4) > 0) {
                $discount = (string) $amount->max_discount_amount;
            }

            return $discount;
        }

        if ($offer->discount_type === DiscountType::FIXED) {
            $amount = $offer->getAmountForCurrency($currencyId);
            if (! $amount?->discount_amount) {
                return null;
            }

            // Don't exceed unit price
            if (bccomp((string) $amount->discount_amount, $unitPrice, 4) > 0) {
                return $unitPrice;
            }

            return (string) $amount->discount_amount;
        }

        return null;
    }

    protected function calculateCartDiscount(Offer $offer, int $currencyId, string $cartTotal): ?string
    {
        if ($offer->discount_type === DiscountType::PERCENTAGE) {
            $discount = bcmul($cartTotal, bcdiv((string) $offer->discount_percentage, '100', 6), 4);

            $amount = $offer->getAmountForCurrency($currencyId);
            if ($amount?->max_discount_amount && bccomp($discount, (string) $amount->max_discount_amount, 4) > 0) {
                $discount = (string) $amount->max_discount_amount;
            }

            return $discount;
        }

        if ($offer->discount_type === DiscountType::FIXED) {
            $amount = $offer->getAmountForCurrency($currencyId);
            if (! $amount?->discount_amount) {
                return null;
            }

            if (bccomp((string) $amount->discount_amount, $cartTotal, 4) > 0) {
                return $cartTotal;
            }

            return (string) $amount->discount_amount;
        }

        return null;
    }
}

# Offers System

## Overview

The Offers system provides structured, rule-based promotions/discounts for the POS and Quotation features. Staff create time-limited offers that automatically apply when products are added as line items.

The naming "Offers" was chosen over "Discounts" to avoid confusion with the existing manual `discount_percentage` on the Customer model.

---

## Offer Types

| Type | Description |
|------|-------------|
| **Product** | Discount on specific individual products (% off or fixed amount off) |
| **Bundle** | Discount when all products in a defined set are purchased together |
| **Minimum Spend** | Cart-level discount when transaction total exceeds a threshold |
| **Category** | Discount on all products in a category |
| **Brand** | Discount on all products from a brand |

---

## Database Schema

### `offers` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string | Required, display name |
| `code` | string, nullable, unique | Internal reference code |
| `type` | string (enum) | product, bundle, minimum_spend, category, brand |
| `discount_type` | string (enum) | percentage, fixed |
| `discount_percentage` | decimal(5,2), nullable | For percentage discounts (0.01–100) |
| `description` | text, nullable | |
| `status` | string, default 'draft' | draft, scheduled, active, expired, disabled |
| `starts_at` | timestamp, nullable | |
| `ends_at` | timestamp, nullable | |
| `is_combinable` | boolean, default false | Can stack with customer discount |
| `priority` | integer, default 0 | Lower = higher priority (for tie-breaking) |
| `apply_to_all_stores` | boolean, default true | |
| `category_id` | FK nullable | For type=category (nullOnDelete) |
| `brand_id` | FK nullable | For type=brand (nullOnDelete) |
| `bundle_mode` | string, nullable | For type=bundle: `whole` or `cheapest_item` |
| Audit trail fields | | created_by, updated_by, previous_updated_by, previous_updated_at |
| `timestamps` | | |
| `soft_deletes` | | |

**Indexes:** `type`, `status`, composite `[status, starts_at, ends_at]`

### `offer_stores` table (pivot)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `offer_id` | FK | cascadeOnDelete |
| `store_id` | FK | restrictOnDelete |
| Unique | | [offer_id, store_id] |

### `offer_amounts` table (per-currency amounts)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `offer_id` | FK | cascadeOnDelete |
| `currency_id` | FK | restrictOnDelete |
| `discount_amount` | decimal(12,4), nullable | Fixed amount off |
| `max_discount_amount` | decimal(12,4), nullable | Cap for % discounts |
| Unique | | [offer_id, currency_id] |

### `offer_products` table (for type=product)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `offer_id` | FK | cascadeOnDelete |
| `product_id` | FK | restrictOnDelete |
| Unique | | [offer_id, product_id] |

### `offer_bundles` table (for type=bundle)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `offer_id` | FK | cascadeOnDelete |
| `product_id` | FK | restrictOnDelete |
| `required_quantity` | integer, default 1 | How many of this product needed |
| Unique | | [offer_id, product_id] |
| Index | | product_id (for fast cart lookups) |

### `offer_minimum_spends` table (per-currency thresholds)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `offer_id` | FK | cascadeOnDelete |
| `currency_id` | FK | restrictOnDelete |
| `minimum_amount` | decimal(12,4) | Threshold to qualify |
| Unique | | [offer_id, currency_id] |

---

## Enums

| Enum | Location | Values |
|------|----------|--------|
| `OfferType` | `app/Enums/OfferType.php` | product, bundle, minimum_spend, category, brand |
| `DiscountType` | `app/Enums/DiscountType.php` | percentage, fixed |
| `OfferStatus` | `app/Enums/OfferStatus.php` | draft, scheduled, active, expired, disabled |
| `BundleMode` | `app/Enums/BundleMode.php` | whole, cheapest_item |

All follow the standard pattern with `label()` and `options()` methods.

---

## Bundle Modes

### `whole` — Discount on Entire Bundle

The percentage or fixed discount applies to the combined total of all bundle items.

**Example:** 10% off when buying Shampoo + Conditioner together.
- Shampoo = $10, Conditioner = $8
- Bundle total = $18
- Discount = 10% of $18 = **$1.80**

### `cheapest_item` — Discount on Cheapest Item

The discount applies only to the cheapest item in the bundle.

**Example:** Buy 2 items, cheapest 50% off.
- Item A = $20, Item B = $15
- Cheapest item = $15
- Discount = 50% of $15 = **$7.50**

---

## Multi-Currency Support

The `offer_amounts` table stores per-currency values:

- **Fixed discounts** (`discount_type = fixed`): The `discount_amount` column holds the fixed amount off per currency. At least one currency amount is required.
- **Percentage discounts** (`discount_type = percentage`): The `max_discount_amount` column optionally caps the calculated percentage discount per currency.

All resolution methods accept a `currency_id` parameter.

---

## Store Scoping

- `apply_to_all_stores = true`: Offer applies everywhere (default).
- `apply_to_all_stores = false`: Only applies to stores linked via the `offer_stores` pivot table.
- Resolution methods always filter by the current store.

---

## Status Lifecycle

```
Draft ──────────────→ Active (manual activation)
  │
  └──→ Scheduled ───→ Active (automatic, when starts_at reached)
                         │
                         ├──→ Expired (automatic, when ends_at passed)
                         │
                         └──→ Disabled (manual)

Disabled ───────────→ Active (manual reactivation)
```

- **Draft**: Created but not yet live. Requires manual activation.
- **Scheduled**: Has a future `starts_at` date. Automatically transitions to Active.
- **Active**: Currently applying to qualifying transactions.
- **Expired**: Past its `ends_at` date. Automatically transitioned.
- **Disabled**: Manually deactivated. Can be reactivated.

The `offers:update-statuses` command runs every 15 minutes to handle automatic transitions.

---

## Stacking / Priority Rules

1. Each line item gets **at most one** product-level offer (the one producing the highest discount).
2. Category/brand offers compete with product offers — best discount wins.
3. Bundle discount is separate — mode determines how it's applied.
4. Minimum spend is transaction-level (separate from line-item offers).
5. Customer `discount_percentage` is always available; `is_combinable` on the offer controls whether it stacks.
6. `priority` field breaks ties (lower number = higher priority).

---

## Permissions

| Permission | Label | Group |
|-----------|-------|-------|
| `offers.view` | View Offers | Offers |
| `offers.manage` | Manage Offers | Offers |

---

## Routes

| Method | URI | Controller Method | Permission |
|--------|-----|-------------------|------------|
| GET | `/offers` | `index` | offers.view |
| GET | `/offers/create` | `create` | offers.manage |
| POST | `/offers` | `store` | offers.manage |
| GET | `/offers/search-products` | `searchProducts` | offers.manage |
| GET | `/offers/{offer}` | `show` | offers.view |
| GET | `/offers/{offer}/edit` | `edit` | offers.manage |
| PUT | `/offers/{offer}` | `update` | offers.manage |
| DELETE | `/offers/{offer}` | `destroy` | offers.manage |
| POST | `/offers/{offer}/activate` | `activate` | offers.manage |
| POST | `/offers/{offer}/disable` | `disable` | offers.manage |

---

## Service Layer: `OfferService`

Location: `app/Services/OfferService.php`

### CRUD Methods
- `create(array $data, $user): Offer` — DB transaction, create offer + related records
- `update(Offer $offer, array $data, $user): Offer` — DB transaction, sync related records
- `delete(Offer $offer): void` — Soft delete
- `activate(Offer $offer): Offer` — Set status=active
- `disable(Offer $offer): Offer` — Set status=disabled

### Resolution Methods (for POS/Quotation)
- `getActiveOffersForStore(int $storeId): array` — Load all active offers for a store, grouped by type
- `findBestProductOffer(Product $product, int $storeId, int $currencyId, string $unitPrice): ?array` — Find best product-level offer (checks product, category, brand)
- `findApplicableBundleOffers(array $cartProductQuantities, int $storeId, int $currencyId): array` — Detect satisfied bundles
- `findBestMinimumSpendOffer(string $cartTotal, int $storeId, int $currencyId): ?array` — Find best minimum spend offer
- `updateStatuses(): int` — Transition scheduled→active and active→expired

### Performance Strategy
1. On POS page load: query all active offers for the store, eager-load relationships
2. Index product offers by product_id, category offers by category_id, brand offers by brand_id
3. On line item add: O(1) lookup by product_id, then check category/brand — all in-memory
4. Bundle detection: iterate bundle offers (typically <10), check cart contents
5. Minimum spend: recalculate on every cart total change

---

## Shared Offer Components (POS + Quotation)

Both POS and Quotation use shared Vue components for offer UX:

### Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `OfferBrowseDialog` | `resources/js/components/offers/OfferBrowseDialog.vue` | Modal to browse all active offers for the current store |
| `OfferSuggestionBanner` | `resources/js/components/offers/OfferSuggestionBanner.vue` | Auto-popup when applicable offers are detected |
| `OfferTag` | `resources/js/components/offers/OfferTag.vue` | Small tag showing applied offer name on a line item |

### OfferBrowseDialog

- Fetches offers grouped by type from a configurable URL (`fetchUrl` prop)
- Displays offers in sections: Product, Category, Brand, Bundle, Minimum Spend
- Each offer shows: name, discount format (% or $ off), "Combinable" badge
- Staff can tap an offer to select it
- Used in POS (via `/pos/offers`) and Quotation (via `/quotations/offers`)

### OfferSuggestionBanner

- Appears automatically when offers are detected for items in the cart/quotation
- Shows offer name and discount preview for each suggestion
- "Apply" button applies all suggested offers
- "Dismiss" button hides the banner
- **Session dismiss:** "Don't show again this session" checkbox stores dismissed offer IDs in a `Set<number>`. Dismissed offers won't auto-popup again during the current session.

### OfferTag

- Displays applied offer name as a PrimeVue `Tag` with `success` severity
- Used on line items in both POS cart and Quotation form

### Offer UX Behavior

1. **Auto-detection:** Offers resolved server-side on every cart/item change. When a new applicable offer is found, `OfferSuggestionBanner` appears.
2. **Manual browse:** "Offers" button (always visible) opens `OfferBrowseDialog` listing all active offers for the store. Staff can manually select offers.
3. **Session dismiss:** If dismissed with checkbox, offer ID stored in session-level `Set<number>` — won't auto-popup again for current transaction/quotation session.
4. **Display:** Applied offers shown as green `OfferTag` on line items. Transaction-level offers (bundle, min spend) shown in totals panel.

---

## POS Integration

### Server-Side Resolution

All offer resolution happens server-side in `TransactionService`:

- **Adding items:** `findBestProductOffer()` called for each product
- **Cart changes:** `recalculateTotals()` calls `findApplicableBundleOffers()` and `findBestMinimumSpendOffer()`
- **Customer changes:** All item discounts recalculated with new customer percentage

### Discount Application Order

1. Line-item offers (product/category/brand) — per item, automatic
2. Customer discount — per item, automatic when customer set
3. Bundle offer — cart level, automatic when bundle satisfied
4. Minimum spend — cart level, automatic when threshold met
5. Manual discount — cart level, staff-applied

### Calculation Details

See `docs/POINT_OF_SALES.md` for full calculation rules including tax handling, combinability logic, and payment balance.

---

## Quotation Integration

### Current Behavior

- Each line item stores: `offer_id`, `offer_name`, `offer_discount_type`, `offer_discount_amount`, `offer_is_combinable`
- Offers resolved at creation time via `POST /quotations/resolve-offer` — quotation stores a snapshot
- If reopened later, original offer terms are preserved even if the offer has expired
- Staff can manually re-resolve offers for current pricing
- Single and batch offer confirmation dialogs before applying

### Offer Resolution Endpoints

| Endpoint | Description |
|----------|-------------|
| `POST /quotations/resolve-offer` | Resolve best product-level offer for a single item |
| `POST /quotations/resolve-bundle-offers` | Detect satisfied bundle offers across all quotation items |
| `POST /quotations/resolve-minimum-spend-offer` | Find best minimum spend offer for quotation total |

These endpoints reuse `OfferService` methods internally.

---

## Future Features (Phase 2+)

### Buy X Get Y (BXGY)
- New offer type: `bxgy`
- Schema: `offer_bxgy` table with `buy_product_id`, `buy_quantity`, `get_product_id`, `get_quantity`, `get_discount_percentage`
- Example: "Buy 3 shampoos, get 1 conditioner free"

### Quantity Tiers (Volume Discounts)
- New offer type: `quantity_tier`
- Schema: `offer_quantity_tiers` table with `min_quantity`, `max_quantity`, `discount_percentage`, `discount_amount`
- Example: "Buy 1-5: full price, 6-10: 10% off, 11+: 20% off"

### Coupon/Voucher Codes
- New offer type: `coupon`
- Schema: `offer_coupons` table (code, max_uses, uses_count) and `offer_coupon_redemptions` table
- Single-use and multi-use support

### Loyalty Points Redemption
- Integration with existing `customers.loyalty_points` field
- Points-to-discount conversion rate configurable per store

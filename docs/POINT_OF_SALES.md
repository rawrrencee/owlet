# Point of Sales (POS) Development Guide

## Overview

The POS module enables staff to process retail sales with support for product pricing, offers/promotions, multi-method payments, suspended transactions, post-sale modifications, refunds, and complete version history.

---

## Product Display in POS

### Data Source

The POS uses a single query to fetch displayable products:

```php
Product::forPosDisplay()
    ->active()
    ->whereHas('productStores', fn($q) =>
        $q->where('store_id', $currentStoreId)->where('is_active', true)
    )
    ->with(['variants' => fn($q) => $q->active()])
    ->withCount('variants')
    ->get();
```

The `forPosDisplay()` scope returns products where `parent_product_id IS NULL`, which includes:
- **Standalone products** - Regular products with no variants
- **Parent products** - Products that have variants (children)

Variant products (where `parent_product_id IS NOT NULL`) are NOT displayed directly in the POS list.

### Product Types

| Type | parent_product_id | variants_count | POS Behavior |
|------|-------------------|----------------|--------------|
| Standalone | NULL | 0 | Add directly to cart |
| Parent | NULL | > 0 | Show variant selector, then add selected variant |
| Variant | set | N/A | Not shown in list, only via parent selection |

### Selection Flow

1. POS displays all products from `forPosDisplay()` query
2. User taps a product
3. Check `has_variants` (or `variants_count > 0`):
   - **If false**: Add product directly to cart
   - **If true**: Show `ProductVariantSelector` dialog with available variants
4. User selects specific variant from dialog
5. Add the selected variant to cart

### Cart Line Items

When adding to cart, always add the specific product:
- For standalone products: add the product itself
- For parent products: add the selected **variant** (child), not the parent

The cart should store the actual sellable product ID, which will always be either a standalone product or a variant.

---

## Pricing in POS

### Price Resolution Order

1. **Store-specific price** (`product_store_prices`) - Highest priority
2. **Base price** (`product_prices`) - Fallback if no store-specific price

### Variant Pricing

Each variant has its own pricing. Variants do NOT automatically inherit parent pricing - each variant must have its own price configuration.

---

## Transaction System

### Transaction Number Format

`TXN-{STORE_CODE}-{YYYYMMDD}-{NNNN}`

Example: `TXN-SG01-20260217-0001`

### Transaction Statuses

| Status | Description | Transitions To |
|--------|-------------|----------------|
| **Draft** | Active sale in progress | Completed, Suspended, Voided |
| **Suspended** | Parked transaction | Draft (via Resume) |
| **Completed** | Finalized sale, inventory decremented | Voided |
| **Voided** | Cancelled transaction | (terminal state) |

### Schema: `transactions`

| Column | Type | Notes |
|--------|------|-------|
| `transaction_number` | string, unique | Auto-generated |
| `store_id` | FK stores | |
| `employee_id` | FK employees | Salesperson |
| `customer_id` | FK customers, nullable | |
| `currency_id` | FK currencies | |
| `status` | string (enum) | draft, suspended, completed, voided |
| `checkout_date` | timestamp, nullable | Set on completion |
| `subtotal` | decimal(12,4) | Sum of line_subtotals |
| `offer_discount` | decimal(12,4) | Sum of line-item offer discounts |
| `bundle_discount` | decimal(12,4) | From bundle offer |
| `minimum_spend_discount` | decimal(12,4) | From min spend offer |
| `customer_discount` | decimal(12,4) | Sum of customer discounts |
| `manual_discount` | decimal(12,4) | Staff-applied manual discount |
| `tax_percentage` | decimal(5,2) | Snapshot from store |
| `tax_inclusive` | boolean | Snapshot from store |
| `tax_amount` | decimal(12,4) | |
| `total` | decimal(12,4) | Final amount |
| `amount_paid` | decimal(12,4) | Sum of payments |
| `balance_due` | decimal(12,4) | Remaining owed |
| `change_amount` | decimal(12,4) | Cash overpayment to return |
| `refund_amount` | decimal(12,4) | Cumulative refunds |
| `bundle_offer_id` / `bundle_offer_name` | | Snapshot of applied bundle offer |
| `minimum_spend_offer_id` / `minimum_spend_offer_name` | | Snapshot of applied min spend offer |
| `customer_discount_percentage` | decimal(5,2) | Snapshot from customer |
| `version_count` | integer | Total version snapshots |
| Audit trail | | created_by, updated_by, etc. |

### Schema: `transaction_items`

| Column | Type | Notes |
|--------|------|-------|
| `product_id` | FK products | |
| `product_name`, `product_number`, `variant_name`, `barcode` | strings | Snapshots |
| `quantity` | integer | |
| `cost_price` | decimal(12,4), nullable | For margin reporting |
| `unit_price` | decimal(12,4) | Resolved selling price |
| `offer_id` / `offer_name` | | Applied offer reference + snapshot |
| `offer_discount_type` | string, nullable | percentage or fixed |
| `offer_discount_amount` | decimal(12,4) | Per-unit offer discount |
| `offer_is_combinable` | boolean, nullable | Can stack with customer discount |
| `customer_discount_percentage` | decimal(5,2), nullable | |
| `customer_discount_amount` | decimal(12,4) | Per-unit customer discount |
| `line_subtotal` | decimal(12,4) | unit_price * quantity |
| `line_discount` | decimal(12,4) | Total line discount |
| `line_total` | decimal(12,4) | line_subtotal - line_discount |
| `is_refunded` | boolean | |
| `refund_reason` | string, nullable | |
| `sort_order` | integer | Display order |

### Schema: `transaction_payments`

| Column | Type | Notes |
|--------|------|-------|
| `payment_mode_id` | FK payment_modes | |
| `payment_mode_name` | string | Snapshot |
| `amount` | decimal(12,4) | |
| `payment_data` | json, nullable | Card ref, bank details, etc. |
| `row_number` | integer | Sequential display order |
| `balance_after` | decimal(12,4) | Running balance after this payment |
| `created_by` | FK users | |

### Schema: `transaction_versions`

| Column | Type | Notes |
|--------|------|-------|
| `version_number` | integer | Sequential per transaction |
| `change_type` | string (enum) | See change types below |
| `changed_by` | FK users | |
| `change_summary` | string | Human-readable description |
| `snapshot_items` | json | All items at this version |
| `snapshot_payments` | json | All payments at this version |
| `snapshot_totals` | json | All totals at this version |
| `diff_data` | json, nullable | |

**Change Types:** `created`, `item_added`, `item_removed`, `item_modified`, `customer_changed`, `payment_added`, `payment_removed`, `discount_applied`, `offer_applied`, `completed`, `suspended`, `resumed`, `voided`, `refund`

---

## Calculation Rules

### Per-Line-Item Calculation

```
line_subtotal = unit_price * quantity

# Offer discount (per-unit applied to quantity)
offer_total = offer_discount_amount * quantity

# Customer discount (percentage off line subtotal)
customer_total = line_subtotal * (customer_discount_percentage / 100)

# Combinability rules:
if offer exists AND customer discount exists:
    if offer.is_combinable:
        line_discount = offer_total + customer_total    # Stack both
    else:
        line_discount = max(offer_total, customer_total) # Take better
else:
    line_discount = offer_total OR customer_total        # Whichever exists

line_discount = min(line_discount, line_subtotal)        # Never exceed cost
line_total = line_subtotal - line_discount
```

### Transaction-Level Calculation

```
subtotal = sum(line_subtotal for non-refunded items)
after_line_discounts = sum(line_total for non-refunded items)

# Bundle offer (cart-level)
bundle_discount = OfferService.findApplicableBundleOffers() → best bundle discount

# Minimum spend offer (cart-level)
minimum_spend_discount = OfferService.findBestMinimumSpendOffer(after_line_discounts)

# Manual discount
after_discounts = after_line_discounts - bundle_discount - min_spend_discount - manual_discount
after_discounts = max(0, after_discounts)

# Tax calculation
if tax_inclusive:
    tax_amount = after_discounts - (after_discounts / (1 + tax_rate))
    total = after_discounts
else (tax exclusive):
    tax_amount = after_discounts * tax_rate
    total = after_discounts + tax_amount

# Payment balance
balance_due = max(0, total - amount_paid)
change_amount = max(0, amount_paid - total)
```

### Discount Application Order

1. **Line-item offers** (product/category/brand) - per item, automatic
2. **Customer discount** - per item, automatic when customer set
3. **Bundle offer** - cart level, automatic when bundle satisfied
4. **Minimum spend offer** - cart level, automatic when threshold met
5. **Manual discount** - cart level, staff-applied

---

## Payment Split Flow

Transactions support split payments across multiple methods (Cash, Card, PayNow, Bank Transfer, etc.).

1. Staff adds first payment (e.g., $50 Cash)
2. System calculates `balance_after` = total - cumulative payments
3. Staff adds second payment (e.g., $30 Card)
4. Continue until `balance_due <= 0`
5. If overpaid (cash), `change_amount` shows how much to return

**Completion requires:** `balance_due <= 0` (fully paid or overpaid)

Each payment records: method, amount, running balance, optional payment data (card reference, etc.), and who processed it.

---

## Suspended Transaction Flow

Transactions can be "parked" (suspended) and resumed later.

1. **Suspend:** Staff clicks Suspend on a draft transaction. Status changes to `suspended`.
2. **Browse:** SuspendedDrawer shows all suspended transactions for current store with resume button.
3. **Resume:** Staff selects a suspended transaction. If an active draft exists, confirm action first. Selected transaction returns to `draft` status.

---

## Refund Flow

Refunds are processed on completed transactions. Both partial and full refunds are supported.

### Partial Refund (returning some quantity)
- Item quantity is **reduced** (e.g., bought 5, refund 2 → quantity becomes 3)
- Item is NOT marked as refunded (continues contributing to totals with new qty)
- Inventory is restored for the refunded quantity

### Full Refund (returning all of an item)
- Item is marked `is_refunded = true` with `refund_reason`
- Item excluded from all total calculations
- Full quantity restored to inventory

### Refund Calculation
1. Mark/reduce items per refund request
2. Re-resolve offers (some may no longer apply with fewer items)
3. Recalculate all totals
4. `refund_amount = old_total - new_total`
5. Cumulative `refund_amount` tracked on transaction

---

## Inventory Integration

### Activity Codes

| Code | Label | Trigger |
|------|-------|---------|
| `SI` | Sold Item | Transaction completed - quantity decremented |
| `RI` | Refund Item | Item refunded or transaction voided - quantity restored |
| `TA` | Transaction Adjustment | Item quantity changed on completed transaction |

### Inventory Flow

- **Sale completion:** For each item, create `SI` log, decrement `ProductStore.quantity`
- **Refund:** For each refunded item, create `RI` log, increment `ProductStore.quantity`
- **Post-completion adjustment:** Create `TA` log, adjust `ProductStore.quantity` by difference
- **Void:** For each non-refunded item, create `RI` log, restore full `ProductStore.quantity`

Inventory is only modified on completion (not when items are added to a draft). This prevents stock discrepancies from abandoned carts.

---

## Version History & Diff System

Every mutation to a transaction creates an immutable `TransactionVersion` record containing:
- **Full snapshot** of all items, payments, and totals at that moment
- **Change type** identifying what happened
- **Change summary** in human-readable text
- **Who** made the change and **when**

This enables:
- Complete audit trail of every transaction modification
- Version-to-version diff comparison in the UI
- Replay of transaction state at any point in time

The frontend `VersionDiffView` component computes diffs between any two versions, highlighting items added (green), removed (red), and modified (yellow), plus totals changes.

---

## Offer Stacking Rules

See `docs/OFFERS.md` for full offer system documentation. Summary:

1. Each line item gets **at most one** product-level offer (best discount wins)
2. Category/brand offers compete with product offers at the line-item level
3. Bundle offer is separate (cart-level)
4. Minimum spend is separate (cart-level)
5. Customer `discount_percentage` stacks per `is_combinable` flag on the offer
6. All automatic offers are re-resolved on every cart change

---

## Permissions

| Permission | Description |
|-----------|-------------|
| `pos.access` | Access the POS page |
| `transactions.view` | View transaction history |
| `transactions.manage` | Manage transactions |
| `process_sales` | Store-level: process sales |
| `void_sales` | Store-level: void transactions |
| `apply_discounts` | Store-level: apply manual discounts |
| `view_transactions` | Store-level: view store transactions |

---

## Routes

### POS Routes (JSON responses)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/pos` | POS page (Inertia) |
| GET | `/pos/products` | Product grid data |
| GET | `/pos/search-products` | Product search |
| GET | `/pos/search-customers` | Customer search |
| GET | `/pos/suspended` | Suspended transactions list |
| GET | `/pos/offers` | Active offers for store |
| POST | `/pos/transactions` | Create new transaction |
| GET | `/pos/transactions/{id}` | Get transaction |
| POST | `/pos/transactions/{id}/items` | Add item |
| PUT | `/pos/transactions/{id}/items/{item}` | Update item |
| DELETE | `/pos/transactions/{id}/items/{item}` | Remove item |
| PUT | `/pos/transactions/{id}/customer` | Set customer |
| POST | `/pos/transactions/{id}/payments` | Add payment |
| DELETE | `/pos/transactions/{id}/payments/{p}` | Remove payment |
| POST | `/pos/transactions/{id}/complete` | Complete transaction |
| POST | `/pos/transactions/{id}/suspend` | Suspend transaction |
| POST | `/pos/transactions/{id}/resume` | Resume transaction |
| POST | `/pos/transactions/{id}/void` | Void transaction |
| POST | `/pos/transactions/{id}/refund` | Process refund |

### Transaction History Routes (Inertia pages)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/transactions` | Transaction list |
| GET | `/transactions/{id}` | Transaction detail |
| GET | `/transactions/{id}/versions` | Version history |
| GET | `/transactions/{id}/versions/{v}/diff` | Version diff |

---

## Key Files

| File | Purpose |
|------|---------|
| `app/Services/TransactionService.php` | Core business logic |
| `app/Models/Transaction.php` | Transaction model |
| `app/Models/TransactionItem.php` | Line item model |
| `app/Models/TransactionPayment.php` | Payment model |
| `app/Models/TransactionVersion.php` | Version history model |
| `app/Enums/TransactionStatus.php` | Status enum |
| `app/Enums/TransactionChangeType.php` | Change type enum |
| `app/Http/Controllers/TransactionController.php` | POS controller |
| `app/Http/Controllers/TransactionHistoryController.php` | History controller |
| `resources/js/pages/Pos/Index.vue` | POS frontend |
| `resources/js/pages/Transactions/Index.vue` | Transaction list |
| `resources/js/pages/Transactions/View.vue` | Transaction detail |

---

## Barcode Scanning

Products can be looked up by:
- `product_number` (SKU)
- `barcode`

Both standalone products and variants have these fields, so scanning works uniformly.

## Search

Use the existing `Product::search()` scope which searches by name, number, and barcode.

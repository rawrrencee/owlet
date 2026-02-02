# Point of Sales (POS) Development Guide

## Overview

This document outlines the planned architecture for the POS module, including how products and variants will be displayed and selected.

## Product Display in POS

### Data Source

The POS will use a single, clean query to fetch displayable products:

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

### Variant Selector Component

A `ProductVariantSelector.vue` component will be created to handle variant selection:

```vue
<Dialog v-model:visible="visible" modal header="Select Option">
    <div class="grid gap-2">
        <Button
            v-for="variant in variants"
            :key="variant.id"
            :label="variant.variant_name"
            :disabled="!variant.is_active"
            @click="selectVariant(variant)"
            class="w-full"
        />
    </div>
</Dialog>
```

### Cart Line Items

When adding to cart, always add the specific product:
- For standalone products: add the product itself
- For parent products: add the selected **variant** (child), not the parent

The cart should store the actual sellable product ID, which will always be either a standalone product or a variant.

## Pricing in POS

### Price Resolution Order

1. **Store-specific price** (`product_store_prices`) - Highest priority
2. **Base price** (`product_prices`) - Fallback if no store-specific price

Use the existing `ProductStorePrice::getEffectivePrice()` method or similar logic.

### Variant Pricing

Each variant has its own pricing (inherited product infrastructure). Variants do NOT automatically inherit parent pricing - each variant must have its own price configuration.

## Inventory in POS

### Stock Checking

Before adding to cart, check inventory:
```php
$productStore = ProductStore::where('product_id', $productId)
    ->where('store_id', $currentStoreId)
    ->first();

if ($productStore && $productStore->quantity < $requestedQuantity) {
    // Show low stock warning or prevent addition
}
```

### Stock Decrement

When a sale is completed, decrement the quantity:
```php
ProductStore::where('product_id', $productId)
    ->where('store_id', $currentStoreId)
    ->decrement('quantity', $soldQuantity);
```

## Permissions

POS should respect existing permissions:
- `products.view` - Required to see products
- `products.view_cost_price` - Controls cost price visibility (typically hidden in POS)
- Store access permissions - Only show products from stores the employee can access

## Future Considerations

### Barcode Scanning

Products can be looked up by:
- `product_number` (SKU)
- `barcode`

Both standalone products and variants have these fields, so scanning works uniformly.

### Item Kits (Bundles)

The legacy system had Item Kits for bundled products. If this feature is needed:
- Create a separate `ItemKit` model
- Item Kits would appear alongside products in POS
- Selecting an Item Kit adds multiple products to cart

### Search

Use the existing `Product::search()` scope which searches by name, number, and barcode:
```php
Product::forPosDisplay()
    ->search($searchTerm)
    ->active()
    // ... rest of query
```

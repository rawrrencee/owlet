# Product Model

This document outlines the Product model for Owlet, which replaces the legacy Item model from goldlink-api.

## Overview

Product represents items sold in the POS system. Each product belongs to a brand, category, subcategory, and supplier. Products can be assigned to multiple stores with store-specific pricing and inventory quantities. Pricing supports multiple currencies with a fallback system from store-specific prices to base product prices.

## Database Schema

### products Table

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigIncrements | No | - | Primary key |
| brand_id | foreignId | No | - | FK to brands.id |
| category_id | foreignId | No | - | FK to categories.id |
| subcategory_id | foreignId | No | - | FK to subcategories.id |
| supplier_id | foreignId | No | - | FK to suppliers.id |
| product_name | string(255) | No | - | Display name of the product |
| product_number | string(255) | No | - | Unique SKU/item number (uppercase, no spaces) |
| barcode | string(255) | Yes | null | UPC/EAN/ISBN barcode (separate from SKU) |
| supplier_number | string(255) | Yes | null | Supplier's product code/reference |
| description | text | Yes | null | Product description |
| tags | json | Yes | null | Flexible tags for filtering/categorization |
| cost_price_remarks | string(255) | Yes | null | Notes about cost price |
| image_path | string(255) | Yes | null | Path to product image |
| image_filename | string(255) | Yes | null | Original filename |
| image_mime_type | string(100) | Yes | null | MIME type of image |
| weight | decimal(10,3) | Yes | null | Product weight value |
| weight_unit | enum | No | 'kg' | Weight unit: 'kg', 'g', 'lb', 'oz' |
| is_active | boolean | No | true | Whether product is active |
| created_by | foreignId | Yes | null | FK to users.id (creator) |
| updated_by | foreignId | Yes | null | FK to users.id (last updater) |
| previous_updated_by | foreignId | Yes | null | FK to users.id (prior updater) |
| previous_updated_at | timestamp | Yes | null | Timestamp of prior update |
| created_at | timestamp | Yes | - | Laravel timestamp |
| updated_at | timestamp | Yes | - | Laravel timestamp |
| deleted_at | timestamp | Yes | null | Soft delete timestamp |

**Indexes:**
- Unique index on `product_number`
- Index on `barcode` (nullable, not unique - same barcode may exist for variants)
- Index on `brand_id`
- Index on `category_id`
- Index on `subcategory_id`
- Index on `supplier_id`
- Index on `is_active`

### product_prices Table

Base prices for each currency the product supports.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigIncrements | No | - | Primary key |
| product_id | foreignId | No | - | FK to products.id (cascadeOnDelete) |
| currency_id | foreignId | No | - | FK to currencies.id |
| cost_price | decimal(19,4) | Yes | null | Base cost price in this currency |
| unit_price | decimal(19,4) | Yes | null | Base selling price in this currency |
| created_at | timestamp | Yes | - | Laravel timestamp |
| updated_at | timestamp | Yes | - | Laravel timestamp |

**Indexes:**
- Unique composite index on `[product_id, currency_id]`

### product_stores Table

Links products to stores with store-specific inventory.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigIncrements | No | - | Primary key |
| product_id | foreignId | No | - | FK to products.id (cascadeOnDelete) |
| store_id | foreignId | No | - | FK to stores.id |
| quantity | integer | No | 0 | Stock quantity at this store |
| is_active | boolean | No | true | Whether product is active at this store |
| created_at | timestamp | Yes | - | Laravel timestamp |
| updated_at | timestamp | Yes | - | Laravel timestamp |

**Indexes:**
- Unique composite index on `[product_id, store_id]`
- Index on `is_active`

### product_store_prices Table

Store-specific price overrides per currency.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigIncrements | No | - | Primary key |
| product_store_id | foreignId | No | - | FK to product_stores.id (cascadeOnDelete) |
| currency_id | foreignId | No | - | FK to currencies.id |
| cost_price | decimal(19,4) | Yes | null | Store-specific cost (null = use base) |
| unit_price | decimal(19,4) | Yes | null | Store-specific price (null = use base) |
| created_at | timestamp | Yes | - | Laravel timestamp |
| updated_at | timestamp | Yes | - | Laravel timestamp |

**Indexes:**
- Unique composite index on `[product_store_id, currency_id]`

## Multi-Currency Pricing Architecture

Products support pricing in multiple currencies with store-level overrides:

```
products (metadata only, no prices)
    ↓
product_prices (product_id, currency_id, cost_price, unit_price)
    - Base prices for each currency the product supports
    ↓
product_stores (product_id, store_id, quantity, is_active)
    - Store assignment with inventory
    ↓
product_store_prices (product_store_id, currency_id, cost_price, unit_price)
    - Store-specific price overrides per currency
    - If null, falls back to product_prices for that currency
```

### Pricing Fallback Logic

For a product at a store in a given currency:
1. Check `product_store_prices` for that currency
2. If null (or no record), fall back to `product_prices` for that currency

### Business Rules

- Product base prices are optional; user chooses which currencies to set prices for
- When assigning a product to a store, for each store currency:
  - If product has a base price for that currency: store price is optional (falls back to base)
  - If product has NO base price for that currency: store price is REQUIRED (enforced in UI)

## Model Relationships

### Product Model

```php
class Product extends Model
{
    use HasAuditTrail, SoftDeletes;

    // Classification
    public function brand(): BelongsTo
    public function category(): BelongsTo
    public function subcategory(): BelongsTo
    public function supplier(): BelongsTo

    // Pricing
    public function prices(): HasMany  // ProductPrice
    public function getPriceForCurrency(int $currencyId): ?ProductPrice

    // Store relationships
    public function stores(): BelongsToMany  // via product_stores pivot
    public function productStores(): HasMany // ProductStore model
    public function activeStores(): BelongsToMany

    // Audit trail
    public function createdBy(): BelongsTo  // User
    public function updatedBy(): BelongsTo  // User
    public function previousUpdatedBy(): BelongsTo  // User
}
```

### ProductPrice Model

```php
class ProductPrice extends Model
{
    public function product(): BelongsTo
    public function currency(): BelongsTo
}
```

### ProductStore Model

```php
class ProductStore extends Model
{
    public function product(): BelongsTo
    public function store(): BelongsTo
    public function storePrices(): HasMany  // ProductStorePrice

    // Effective price helpers (with fallback)
    public function getEffectiveCostPrice(int $currencyId): ?string
    public function getEffectiveUnitPrice(int $currencyId): ?string
}
```

### ProductStorePrice Model

```php
class ProductStorePrice extends Model
{
    public function productStore(): BelongsTo
    public function currency(): BelongsTo

    // Effective price helpers (with fallback to base)
    public function getEffectiveCostPrice(): ?string
    public function getEffectiveUnitPrice(): ?string
}
```

## Changes from Legacy Item Model

### Renamed

| Legacy (Item) | New (Product) | Reason |
|---------------|---------------|--------|
| item_name | product_name | Renamed entity |
| item_number | product_number | Renamed entity |
| img_url | image_path + image_filename + image_mime_type | Match existing pattern (Brand, etc.) |
| active | is_active | Match existing pattern |
| items_stores | product_stores | Renamed entity |
| cost_price, unit_price (on items table) | product_prices table | Multi-currency support |

### Retained As-Is

- brand_id, category_id, subcategory_id, supplier_id
- cost_price_remarks
- description, supplier_number
- created_by, updated_by
- Soft deletes
- Store-specific pricing and quantities via pivot table

### New Properties (Shopify-Inspired)

| Property | Type | Description |
|----------|------|-------------|
| barcode | string | UPC/EAN/ISBN barcode, separate from SKU (product_number) |
| weight | decimal | Product weight for shipping/logistics |
| weight_unit | enum | Weight unit: kg, g, lb, oz |
| tags | json | Flexible tags array for filtering beyond category/subcategory |

These properties enhance the product model with common e-commerce capabilities not present in the legacy system.

## Soft Delete Handling

When a product is soft deleted:
- The `deleted_at` timestamp is set
- The product_number remains unchanged (no suffix modification)
- Validation uses `Rule::unique('products', 'product_number')->whereNull('deleted_at')` to allow reuse of product numbers from soft-deleted products
- All associated product_stores remain but are effectively inaccessible

## Future Considerations

### Product Variants Support

Products can have variants for different options like size, color, material, etc. This will be implemented as:

#### product_options Table
| Column | Type | Description |
|--------|------|-------------|
| id | bigIncrements | Primary key |
| product_id | foreignId | FK to products.id |
| name | string(100) | Option name (e.g., "Size", "Color") |
| position | integer | Display order |

#### product_option_values Table
| Column | Type | Description |
|--------|------|-------------|
| id | bigIncrements | Primary key |
| product_option_id | foreignId | FK to product_options.id |
| value | string(100) | Option value (e.g., "Small", "Red") |
| position | integer | Display order |

#### product_variants Table
| Column | Type | Description |
|--------|------|-------------|
| id | bigIncrements | Primary key |
| product_id | foreignId | FK to products.id (parent product) |
| sku | string(255) | Unique variant SKU |
| barcode | string(255) | Variant-specific barcode |
| cost_price | decimal(19,4) | Variant cost price |
| unit_price | decimal(19,4) | Variant unit price |
| weight | decimal(10,3) | Variant weight |
| image_path | string(255) | Variant-specific image |
| is_active | boolean | Variant active status |

#### product_variant_options Table (Pivot)
| Column | Type | Description |
|--------|------|-------------|
| product_variant_id | foreignId | FK to product_variants.id |
| product_option_value_id | foreignId | FK to product_option_values.id |

**Example:**
- Product: "T-Shirt"
  - Options: Size (S, M, L), Color (Red, Blue)
  - Variants: T-Shirt S/Red, T-Shirt S/Blue, T-Shirt M/Red, etc.

### ProductKit (Bundle) Support

The legacy system had ItemKit for bundled products. This will be implemented later as:

- `product_kits` table - Kit definitions
- `product_kit_items` pivot - Items within a kit with quantities
- `product_kit_stores` pivot - Store-specific kit availability

### Discount Support

Discounts will be added in a future phase. Potential approaches:
- Discount rules that apply to products, categories, or brands
- Time-based discounts (promotions)
- Customer-specific discounts
- Quantity-based discounts (bulk pricing)

## Permissions

| Permission | Description |
|------------|-------------|
| products.view | View products list and details |
| products.create | Create new products |
| products.edit | Edit existing products |
| products.delete | Delete products |
| products.view_cost_price | View cost price information (sensitive) |
| products.manage_inventory | Manage stock quantities |

## API Endpoints (Future)

When API support is needed:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/products | List products (paginated) |
| GET | /api/v1/products/{id} | Get product details |
| POST | /api/v1/products | Create product |
| PUT | /api/v1/products/{id} | Update product |
| DELETE | /api/v1/products/{id} | Soft delete product |
| GET | /api/v1/stores/{id}/products | List products for a store |

## Web Routes (Inertia)

| Method | Route | Controller Method | Permission | Description |
|--------|-------|-------------------|------------|-------------|
| GET | /products | index | products.view | Products list page |
| GET | /products/create | create | products.create | Create product form |
| POST | /products | store | products.create | Store new product |
| GET | /products/{id} | show | products.view | View product details |
| GET | /products/{id}/edit | edit | products.edit | Edit product form |
| PUT | /products/{id} | update | products.edit | Update product |
| DELETE | /products/{id} | destroy | products.delete | Soft delete product |
| POST | /products/{id}/restore | restore | products.delete | Restore soft-deleted product |
| GET | /products/{id}/image | showImage | products.view | View product image |
| POST | /products/{id}/image | uploadImage | products.edit | Upload product image |
| DELETE | /products/{id}/image | deleteImage | products.edit | Delete product image |

## Validation Rules

### Create/Update Product

```php
[
    'product_name' => 'required|string|max:255',
    'product_number' => [
        'required',
        'string',
        'max:255',
        Rule::unique('products', 'product_number')->whereNull('deleted_at'),
    ],
    'barcode' => 'nullable|string|max:255',
    'brand_id' => 'required|exists:brands,id',
    'category_id' => 'required|exists:categories,id',
    'subcategory_id' => 'required|exists:subcategories,id',
    'supplier_id' => 'required|exists:suppliers,id',
    'supplier_number' => 'nullable|string|max:255',
    'description' => 'nullable|string',
    'tags' => 'nullable|array',
    'tags.*' => 'string|max:50',
    'cost_price_remarks' => 'nullable|string|max:255',
    'weight' => 'nullable|numeric|min:0',
    'weight_unit' => 'required|in:kg,g,lb,oz',
    'image' => 'nullable|image|max:5120',
    'is_active' => 'boolean',

    // Base prices (per currency)
    'prices' => 'nullable|array',
    'prices.*.currency_id' => 'required|exists:currencies,id',
    'prices.*.cost_price' => 'nullable|numeric|min:0',
    'prices.*.unit_price' => 'nullable|numeric|min:0',

    // Store assignments
    'stores' => 'nullable|array',
    'stores.*.store_id' => 'required|exists:stores,id',
    'stores.*.quantity' => 'nullable|integer|min:0',
    'stores.*.is_active' => 'boolean',
    'stores.*.prices' => 'nullable|array',
    'stores.*.prices.*.currency_id' => 'required|exists:currencies,id',
    'stores.*.prices.*.cost_price' => 'nullable|numeric|min:0',
    'stores.*.prices.*.unit_price' => 'nullable|numeric|min:0',
]
```

## Business Logic

### Product Number Normalization

Product numbers are automatically:
- Converted to uppercase
- Stripped of leading/trailing whitespace

### Deactivation Cascade

When a product is deactivated (`is_active = false`):
- Consider setting all associated product_stores to `is_active = false`

### Tags Handling

Tags are stored as a JSON array of strings:
```json
["bestseller", "seasonal", "clearance"]
```

Use Laravel's JSON casting:
```php
protected function casts(): array
{
    return [
        'tags' => 'array',
    ];
}
```

Searching by tags:
```php
// Find products with a specific tag
Product::whereJsonContains('tags', 'bestseller')->get();

// Find products with any of multiple tags
Product::where(function ($query) {
    $query->whereJsonContains('tags', 'seasonal')
          ->orWhereJsonContains('tags', 'clearance');
})->get();
```

# Product Model

This document outlines the Product model for Owlet, which replaces the legacy Item model from goldlink-api.

## Overview

Product represents items sold in the POS system. Each product belongs to a brand, category, subcategory, and supplier. Products can be assigned to multiple stores with store-specific pricing and inventory quantities.

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
| cost_price | decimal(19,4) | No | 0 | Base cost price |
| cost_price_remarks | string(255) | Yes | null | Notes about cost price |
| unit_price | decimal(19,4) | No | - | Base selling/unit price |
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

### product_stores Table (Pivot)

Links products to stores with store-specific pricing and inventory.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigIncrements | No | - | Primary key |
| product_id | foreignId | No | - | FK to products.id |
| store_id | foreignId | No | - | FK to stores.id |
| cost_price | decimal(19,4) | Yes | null | Store-specific cost (null = use product default) |
| unit_price | decimal(19,4) | Yes | null | Store-specific price (null = use product default) |
| quantity | integer | No | 0 | Stock quantity at this store |
| is_active | boolean | No | true | Whether product is active at this store |
| created_at | timestamp | Yes | - | Laravel timestamp |
| updated_at | timestamp | Yes | - | Laravel timestamp |

**Indexes:**
- Unique composite index on `[product_id, store_id]`
- Index on `product_id`
- Index on `store_id`
- Index on `is_active`

## Model Relationships

### Product Model

```php
class Product extends Model
{
    // Relationships
    public function brand(): BelongsTo
    public function category(): BelongsTo
    public function subcategory(): BelongsTo
    public function supplier(): BelongsTo
    public function createdBy(): BelongsTo  // User
    public function updatedBy(): BelongsTo  // User
    public function previousUpdatedBy(): BelongsTo  // User

    // Store relationships
    public function stores(): BelongsToMany  // via product_stores pivot
    public function productStores(): HasMany // ProductStore model
}
```

### ProductStore Model

```php
class ProductStore extends Model
{
    public function product(): BelongsTo
    public function store(): BelongsTo
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

### Retained As-Is

- brand_id, category_id, subcategory_id, supplier_id
- cost_price, unit_price, cost_price_remarks
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

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| GET | /products | index | Products list page |
| GET | /products/create | create | Create product form |
| POST | /products | store | Store new product |
| GET | /products/{id} | show | View product details |
| GET | /products/{id}/edit | edit | Edit product form |
| PUT | /products/{id} | update | Update product |
| DELETE | /products/{id} | destroy | Soft delete product |

## Validation Rules

### Create/Update Product

```php
[
    'product_name' => 'required|string|max:255',
    'product_number' => 'required|string|max:255|unique:products,product_number',
    'barcode' => 'nullable|string|max:255',
    'brand_id' => 'required|exists:brands,id',
    'category_id' => 'required|exists:categories,id',
    'subcategory_id' => 'required|exists:subcategories,id',
    'supplier_id' => 'required|exists:suppliers,id',
    'supplier_number' => 'nullable|string|max:255',
    'description' => 'nullable|string',
    'tags' => 'nullable|array',
    'tags.*' => 'string|max:50',
    'cost_price' => 'nullable|numeric|min:0',
    'cost_price_remarks' => 'nullable|string|max:255',
    'unit_price' => 'required|numeric|min:0',
    'weight' => 'nullable|numeric|min:0',
    'weight_unit' => 'required|in:kg,g,lb,oz',
    'image' => 'nullable|image|max:2048',
    'is_active' => 'boolean',
]
```

## Business Logic

### Product Number Normalization

Product numbers are automatically:
- Converted to uppercase
- Stripped of leading/trailing whitespace

### Soft Delete Handling

When a product is soft deleted:
- The product_number is appended with `__deleted@{timestamp}` to allow reuse of the number
- All associated product_stores remain but can be cleaned up

### Deactivation Cascade

When a product is deactivated (`is_active = false`):
- All associated product_stores should also be set to `is_active = false`

### Store-Specific Pricing

- If `product_stores.cost_price` is null, use `products.cost_price`
- If `product_stores.unit_price` is null, use `products.unit_price`
- This allows store-level price overrides while maintaining a default

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

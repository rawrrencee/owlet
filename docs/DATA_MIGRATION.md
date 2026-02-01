# Data Migration Module

This module provides a complete solution for migrating data from the legacy Goldlink database to Owlet. It is designed to be self-contained and removable after migration is complete.

## Overview

The data migration module:
- Migrates data from the legacy MySQL database to Owlet's SQLite/MySQL database
- Tracks migration progress at the record level for resumability
- Handles foreign key remapping between legacy and Owlet IDs
- Downloads and re-uploads images/documents from legacy URLs
- Provides a web UI for administrators to run and monitor migrations

## Prerequisites

### 1. Configure Legacy Database Connection

Add the following to your `.env` file:

```env
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=goldlink_api
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=your_password
```

### 2. Run Migrations

```bash
php artisan migrate
```

This creates the `migration_runs` and `migration_logs` tables for tracking progress.

### 3. Ensure Required Currencies Exist

Before running product migrations, ensure these currencies exist in Owlet:
- **SGD** (Singapore Dollar) - Required for all product pricing
- **MYR** (Malaysian Ringgit) - Required for SEIBU TRX store (legacy store ID 16)

## Migration Order

Models must be migrated in dependency order. The following is the recommended order:

### Phase 1: Independent Models (No Dependencies)
1. **Designations** - `designation`
2. **Companies** - `company`
3. **Categories** - `category`
4. **Brands** - `brand`
5. **Suppliers** - `supplier`
6. **Customers** - `customer`

### Phase 2: Store-Related (Depends on Company)
7. **Stores** - `store` (depends on: company)
8. **Subcategories** - `subcategory` (depends on: category)

### Phase 3: Employees (Depends on Multiple)
9. **Employees** - `employee`
10. **Employee-Company** - `employee_company` (depends on: employee, company, designation)
11. **Employee-Store** - `employee_store` (depends on: employee, store)
12. **Employee Contracts** - `employee_contract` (depends on: employee, company)
13. **Employee Insurances** - `employee_insurance` (depends on: employee)
14. **Employee Hierarchy** - `employee_hierarchy` (depends on: employee)

### Phase 4: Products (Depends on Multiple)
15. **Products** - `product` (depends on: brand, category, subcategory, supplier)
16. **Product-Store** - `product_store` (depends on: product, store)

### Phase 5: Timecards (Depends on Employee, Store)
17. **Timecards** - `timecard` (depends on: employee, store)
18. **Timecard Details** - `timecard_detail` (depends on: timecard)

## Field Mappings

### Employee Migration (User + UserProfile → Employee)

| Legacy Table | Legacy Field | Owlet Field | Notes |
|-------------|--------------|-------------|-------|
| `users` | `id` | - | Tracked in migration_logs |
| `users` | `email` | - | Stored in metadata only |
| `users` | `role` | - | Stored in metadata only |
| `users` | `active` | - | Not used (use termination_date) |
| `users_profiles` | `first_name` | `first_name` | Direct |
| `users_profiles` | `last_name` | `last_name` | Direct |
| `users_profiles` | `chinese_name` | `chinese_name` | Direct |
| `users_profiles` | `nric` | `nric` | Direct |
| `users_profiles` | `phone_number` | `phone` | Renamed |
| `users_profiles` | `mobile_number` | `mobile` | Renamed |
| `users_profiles` | `address_1` | `address_1` | Direct |
| `users_profiles` | `address_2` | `address_2` | Direct |
| `users_profiles` | `city` | `city` | Direct |
| `users_profiles` | `state` | `state` | Direct |
| `users_profiles` | `postal_code` | `postal_code` | Direct |
| `users_profiles` | `country` | `country` | Direct (text, not ID) |
| `users_profiles` | `date_of_birth` | `date_of_birth` | Direct |
| `users_profiles` | `gender` | `gender` | Direct |
| `users_profiles` | `race` | `race` | Direct |
| `users_profiles` | `nationality` | `nationality` | Direct (text, not ID) |
| `users_profiles` | `residency_status` | `residency_status` | Direct |
| `users_profiles` | `pr_conversion_date` | `pr_conversion_date` | Direct |
| `users_profiles` | `emergency_name` | `emergency_name` | Direct |
| `users_profiles` | `emergency_relationship` | `emergency_relationship` | Direct |
| `users_profiles` | `emergency_contact` | `emergency_contact` | Direct |
| `users_profiles` | `emergency_address_1` | `emergency_address_1` | Direct |
| `users_profiles` | `emergency_address_2` | `emergency_address_2` | Direct |
| `users_profiles` | `bank_name` | `bank_name` | Direct |
| `users_profiles` | `bank_account_number` | `bank_account_number` | Direct |
| `users_profiles` | `comments` | `notes` | Renamed |
| `users_profiles` | `img_url` | `profile_picture` | Downloaded & re-uploaded |

**Note:** User accounts (login credentials) are NOT migrated. Only Employee records are created. Users will need to be invited via WorkOS.

### Product Migration (Item → Product + ProductPrice)

| Legacy Table | Legacy Field | Owlet Table | Owlet Field | Notes |
|-------------|--------------|-------------|-------------|-------|
| `items` | `item_name` | `products` | `product_name` | Renamed |
| `items` | `item_number` | `products` | `product_number` | Renamed, uppercased |
| `items` | `brand_id` | `products` | `brand_id` | FK remapped |
| `items` | `category_id` | `products` | `category_id` | FK remapped |
| `items` | `subcategory_id` | `products` | `subcategory_id` | FK remapped |
| `items` | `supplier_id` | `products` | `supplier_id` | FK remapped |
| `items` | `supplier_number` | `products` | `supplier_number` | Direct |
| `items` | `description` | `products` | `description` | Direct |
| `items` | `cost_price_remarks` | `products` | `cost_price_remarks` | Direct |
| `items` | `img_url` | `products` | `image_path` | Downloaded & re-uploaded |
| `items` | `active` | `products` | `is_active` | Renamed |
| `items` | `cost_price` | `product_prices` | `cost_price` | SGD currency |
| `items` | `unit_price` | `product_prices` | `unit_price` | SGD currency |

### Product Store Migration (ItemStore → ProductStore + ProductStorePrice)

| Legacy Table | Legacy Field | Owlet Table | Owlet Field | Notes |
|-------------|--------------|-------------|-------------|-------|
| `items_stores` | `item_id` | `product_stores` | `product_id` | FK remapped |
| `items_stores` | `store_id` | `product_stores` | `store_id` | FK remapped |
| `items_stores` | `quantity` | `product_stores` | `quantity` | Direct |
| `items_stores` | `active` | `product_stores` | `is_active` | Renamed |
| `items_stores` | `cost_price` | `product_store_prices` | `cost_price` | Currency based on store |
| `items_stores` | `unit_price` | `product_store_prices` | `unit_price` | Currency based on store |

**Currency Logic:**
- Legacy store ID 16 (SEIBU TRX, Malaysia) → MYR currency
- All other stores → SGD currency

### Customer Migration

| Legacy Field | Owlet Field | Notes |
|--------------|-------------|-------|
| `first_name` | `first_name` | Direct |
| `last_name` | `last_name` | Direct |
| `email` | `email` | Direct |
| `phone_number` | `phone` | Renamed |
| `mobile_number` | `mobile` | Renamed |
| `date_of_birth` | `date_of_birth` | Direct |
| `gender` | `gender` | Direct |
| `race` | `race` | Direct |
| `discount_percentage` | `discount_percentage` | Direct |
| `comments` | `notes` | Renamed |
| `company_name` | - | **Skipped** (not in Owlet schema) |
| `country` | - | **Skipped** (Owlet uses country_id) |
| `city` | - | **Skipped** (saved in metadata) |
| `state` | - | **Skipped** (saved in metadata) |
| `address_1` | - | **Skipped** (saved in metadata) |
| `address_2` | - | **Skipped** (saved in metadata) |
| `postal_code` | - | **Skipped** (saved in metadata) |

## Models Not Ready for Migration

The following legacy models do not have Owlet equivalents yet:

| Legacy Model | Category | Notes |
|-------------|----------|-------|
| `Transaction`, `TransactionItem`, `TransactionPayment` | Sales | Core POS transactions |
| `PendingTransaction`, `PendingTransactionItem` | Sales | Held/pending transactions |
| `ItemKit`, `ItemKitItem`, `ItemKitStore` | Products | Product bundles/kits |
| `Stocktake`, `StocktakeItem` | Inventory | Stock counting |
| `DeliveryOrder`, `DeliveryOrderItem` | Inventory | Stock transfers |
| `InventoryLog` | Inventory | Stock movement history |
| `SalaryVoucher` + related tables | Payroll | Salary processing |
| `UserSalesTarget` | Performance | Sales targets |
| `PermissionRoles` + related | Permissions | Legacy permission system |
| `GlobalSettings` | System | Different settings approach |

## Using the Web UI

1. Navigate to **Management → Data Migration** in the sidebar
2. Verify the legacy database connection is successful
3. Click on each model type to view details and run migration
4. Models with a lock icon have unmet dependencies
5. Use the **Verify** button to check data integrity after migration
6. Use **Retry Failed** to retry any failed records

## Programmatic Usage

```php
use App\DataMigration\Services\MigrationService;

$service = app(MigrationService::class);

// Get status for all models
$status = $service->getAllStatus();

// Get a specific migrator
$migrator = $service->getMigrator('employee');

// Run migration
$run = $migrator->migrate(batchSize: 100);

// Verify migrated data
$result = $migrator->verify();

// Retry failed records
$run = $migrator->retryFailed();
```

## Removal Instructions

After migration is complete and verified, remove the module:

1. Delete the `app/DataMigration/` directory
2. Delete `resources/js/pages/Admin/DataMigration/` directory
3. Remove routes from `routes/web.php`:
   ```php
   // Delete this block:
   Route::prefix('admin/data-migration')->name('admin.data-migration.')->group(function () {
       // ...
   });
   ```
4. Remove nav item from `app/Services/NavigationService.php`:
   ```php
   // Delete this line:
   $managementItems[] = ['title' => 'Data Migration', 'href' => '/admin/data-migration', 'icon' => 'DatabaseZap'];
   ```
5. Remove legacy database config from `config/database.php` (optional)
6. Remove legacy env vars from `.env` (optional)
7. Optionally keep `migration_runs` and `migration_logs` tables for audit purposes

## Troubleshooting

### "Currency not found" Error
Ensure SGD and MYR currencies exist in the currencies table before running product migrations.

### "Company/Store not migrated yet" Error
Dependencies must be migrated first. Check the migration order above.

### Failed Records
Click on the model in the web UI to see failed records with their error messages. Common issues:
- Missing foreign key references
- Invalid data formats
- Network errors when downloading images

### Images Not Migrating
Check that:
- The legacy image URLs are accessible
- The Owlet storage directory is writable
- Network timeouts are not occurring (increase timeout in ImageMigrationService)

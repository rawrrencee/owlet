# Permissions System

This document describes the permission system used in Owlet for controlling access to pages and features.

## Overview

The permission system uses a two-layer architecture:

1. **Global Page Permissions** - Controls access to application pages (Brands, Categories, Suppliers, Stores)
2. **Store Access Permissions** - Controls what a staff member can do with a specific store

Admins always have full access to everything. The permission system primarily affects staff users.

## Two-Layer Architecture

### Layer 1: Global Page Permissions

These permissions control which pages a staff user can access. They are stored in the `employee_permissions` table.

| Permission | Description |
|------------|-------------|
| `brands.view` | Can view the Brands listing and details |
| `brands.manage` | Can create, edit, and delete brands |
| `categories.view` | Can view the Categories listing and details |
| `categories.manage` | Can create, edit, and delete categories |
| `suppliers.view` | Can view the Suppliers listing and details |
| `suppliers.manage` | Can create, edit, and delete suppliers |
| `stores.access` | Can access the Stores listing (filtered to assigned stores) |

### Layer 2: Store Access Permissions

These permissions control what a staff user can do with a specific store. They are stored in the `access_permissions` column of the `employee_stores` table.

| Permission | Description |
|------------|-------------|
| `store.view` | Can view store details |
| `store.edit` | Can edit store settings |
| `store.manage_employees` | Can add/remove employee assignments for the store |
| `store.manage_currencies` | Can manage store currencies |

### Layer 3: Store Operations Permissions (Existing)

These existing permissions control what an employee can do within the store's daily operations. They are stored in the `permissions` column of the `employee_stores` table.

| Permission | Description |
|------------|-------------|
| `view_transactions` | Can view store transactions |
| `view_inventory` | Can view store inventory |
| `view_reports` | Can view store reports |
| `process_sales` | Can process sales |
| `void_sales` | Can void sales |
| `apply_discounts` | Can apply discounts |
| `manage_inventory` | Can manage inventory |
| `stock_transfer` | Can perform stock transfers |
| `add_delivery_order` | Can add delivery orders |
| `edit_delivery_order` | Can edit delivery orders |
| `approve_delivery_order` | Can approve delivery orders |
| `delete_delivery_order` | Can delete delivery orders |

## Database Schema

### employee_permissions table
```
- id (primary key)
- employee_id (foreign key, unique, cascade delete)
- page_permissions (JSON array of permission strings)
- timestamps
```

### employee_stores table (updated)
```
- ... existing columns ...
- permissions (JSON array) - Store operations permissions
- access_permissions (JSON array) - Store access permissions
```

## Backend Implementation

### Constants

Permission constants are defined in:
- `app/Constants/PagePermissions.php` - Global page permissions
- `app/Constants/StoreAccessPermissions.php` - Store access permissions
- `app/Constants/StorePermissions.php` - Store operations permissions (existing)

### PermissionService

The `App\Services\PermissionService` provides methods for checking permissions:

```php
use App\Services\PermissionService;

$permissionService = app(PermissionService::class);

// Check if user can access a page
$canViewBrands = $permissionService->canAccessPage($user, 'brands.view');

// Check if user can access a store (with optional access permission)
$canViewStore = $permissionService->canAccessStore($user, $storeId, 'store.view');

// Get all accessible store IDs for a user
$storeIds = $permissionService->getAccessibleStoreIds($user, 'store.edit');

// Get permissions formatted for frontend
$permissions = $permissionService->getPermissionsForFrontend($user);
```

### Middleware

Use the `permission` middleware alias to protect routes:

```php
// Single permission
Route::middleware('permission:brands.view')->group(function () {
    Route::get('brands', [BrandController::class, 'index']);
});

// For store-specific actions, use the StorePolicy
Route::middleware('permission:stores.access')->group(function () {
    Route::get('stores/{store}', [StoreController::class, 'show']);
});
```

### Policy

The `App\Policies\StorePolicy` handles authorization for store-specific actions:

```php
// In controller
$this->authorize('view', $store);
$this->authorize('update', $store);
$this->authorize('manageEmployees', $store);
$this->authorize('manageCurrencies', $store);
```

## Frontend Implementation

### TypeScript Types

Permission types are defined in `resources/js/types/auth.ts`:

```typescript
type Permissions = {
    is_admin: boolean;
    page_permissions: string[];
    store_permissions: Record<number, {
        access: string[];
        operations: string[];
    }>;
};
```

### usePermissions Composable

The `usePermissions` composable provides reactive permission checking:

```typescript
import { usePermissions } from '@/composables/usePermissions';

const { isAdmin, canAccessPage, canAccessStore, hasStoreOperation } = usePermissions();

// Check admin status
if (isAdmin.value) { /* ... */ }

// Check page access
if (canAccessPage('brands.view')) { /* ... */ }

// Check store access
if (canAccessStore(storeId, 'store.edit')) { /* ... */ }

// Check store operation
if (hasStoreOperation(storeId, 'process_sales')) { /* ... */ }
```

### Route Guard

The `usePermissionGuard` composable in `AppLayout.vue` prevents navigation to unauthorized pages and shows error toasts.

## Configuration UI

Permissions can be configured in two places:

### 1. Users Page (Edit Employee)

- **Stores Tab**: Configure store assignments with access and operation permissions
- **Permissions Tab**: Configure global page permissions (only for staff users)

### 2. Store Edit Page

- **Employees Section**: Configure employee assignments with access and operation permissions

## Adding New Permissions

### Adding a New Page Permission

1. Add constant to `app/Constants/PagePermissions.php`:
   ```php
   public const NEW_PAGE_VIEW = 'new_page.view';
   public const NEW_PAGE_MANAGE = 'new_page.manage';
   ```

2. Add to the `all()` method with label and group

3. Add routes with middleware in `routes/web.php`:
   ```php
   Route::middleware('permission:new_page.view')->group(function () {
       // view routes
   });
   ```

4. Update `NavigationService` to show/hide nav items based on permission

5. Update `usePermissionGuard.ts` with the route mapping

### Adding a New Store Access Permission

1. Add constant to `app/Constants/StoreAccessPermissions.php`

2. Add to the `all()` method with label and group

3. Add policy method to `StorePolicy.php`

4. Use `$this->authorize()` in controller methods

## Testing

Run tests to verify the permission system:
```bash
php artisan test
```

Key test scenarios:
- Admin users have full access to all pages
- Staff without permissions are blocked from all commerce/management pages
- Staff with page permissions can access corresponding pages
- Staff with store access can only see/edit assigned stores
- Navigation only shows permitted pages

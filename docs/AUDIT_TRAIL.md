# Audit Trail

This document describes the audit trail system used across Owlet to track who creates and modifies records.

## Overview

The audit trail system automatically records which user created and last modified a record. It also tracks the previous modifier and timestamp before the most recent update, providing a minimal history of changes.

## Audit Fields

All audited models include these four fields:

| Field | Type | Description |
|-------|------|-------------|
| `created_by` | foreignId (nullable) | User who created the record |
| `updated_by` | foreignId (nullable) | User who last modified the record |
| `previous_updated_by` | foreignId (nullable) | User who modified the record before the last update |
| `previous_updated_at` | timestamp (nullable) | When the previous update occurred |

Additionally, Laravel's built-in `created_at` and `updated_at` timestamps are used.

## Models with Audit Trail

The following models have audit trail fields:

| Model | Category | Notes |
|-------|----------|-------|
| Employee | Core entity | - |
| Customer | Core entity | - |
| Company | Organization | - |
| Store | Organization | - |
| Brand | Master data | Product-related |
| Category | Master data | Product-related |
| Subcategory | Master data | Product-related |
| Supplier | Master data | Product-related |
| Designation | Employee management | - |
| EmployeeContract | Documents | Sensitive employee data |
| EmployeeInsurance | Documents | Sensitive employee data |
| Timecard | Time tracking | - |
| Product | Inventory | Future implementation |

### Models Without Audit Trail

| Model | Reason |
|-------|--------|
| User | Self-referential; User IS the auditor |
| Country | Reference data, rarely changes |
| Currency | Reference data, rarely changes |
| EmployeeStore | Pivot table; changes tracked via parent (Employee) |
| EmployeeCompany | Pivot table; changes tracked via parent (Employee) |
| EmployeeHierarchy | Pivot table; changes tracked via parent (Employee) |
| TimecardDetail | Child records; audit via parent (Timecard) |

## Implementation

### HasAuditTrail Trait

All audited models use the `HasAuditTrail` trait located at `app/Models/Concerns/HasAuditTrail.php`.

```php
use App\Models\Concerns\HasAuditTrail;

class Brand extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        // ... other fields
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];
}
```

The trait automatically:
- Sets `created_by` and `updated_by` to the current user on create
- Saves the previous `updated_by` and `updated_at` before updating
- Sets `updated_by` to the current user on update

### Relationships

The trait provides three relationships:

```php
$model->createdBy;          // User who created
$model->updatedBy;          // User who last updated
$model->previousUpdatedBy;  // User who updated before last
```

### Controller Usage

When returning data for View pages, eager-load the audit relationships:

```php
public function show(Request $request, Brand $brand): InertiaResponse
{
    $brand->load([
        'country',
        'createdBy:id,name',
        'updatedBy:id,name',
        'previousUpdatedBy:id,name',
    ]);

    return Inertia::render('Brands/View', [
        'brand' => (new BrandResource($brand))->resolve(),
    ]);
}
```

**Note:** Use field selection (`:id,name`) to minimize data transfer - the frontend only needs the user's ID and name for display.

## Frontend

### TypeScript Types

Audit types are defined in `resources/js/types/audit.ts`:

```typescript
export interface AuditUser {
    id: number;
    name: string;
}

export interface HasAuditTrail {
    created_by?: AuditUser | null;
    updated_by?: AuditUser | null;
    previous_updated_by?: AuditUser | null;
    previous_updated_at?: string | null;
    created_at: string;
    updated_at: string;
}
```

### AuditInfo Component

Use the `AuditInfo.vue` component to display audit information on View pages:

```vue
<script setup lang="ts">
import Divider from 'primevue/divider';
import AuditInfo from '@/components/AuditInfo.vue';
</script>

<template>
    <!-- ... other content ... -->

    <Divider />
    <AuditInfo
        :created-by="brand.created_by"
        :updated-by="brand.updated_by"
        :previous-updated-by="brand.previous_updated_by"
        :created-at="brand.created_at"
        :updated-at="brand.updated_at"
        :previous-updated-at="brand.previous_updated_at"
    />
</template>
```

The component displays:
- Created by (user name and timestamp)
- Last updated by (user name and timestamp)
- Previously updated by (if available, user name and timestamp)

## Database Migration

The audit fields were added via migration `2026_02_01_000001_add_audit_trail_fields_to_models.php`.

For new models requiring audit trail:

1. Add the trait: `use HasAuditTrail;`
2. Add audit fields to `$fillable`
3. Create a migration to add the columns
4. Update the controller to eager-load relationships
5. Add `AuditInfo` component to the View page

## API Resources

When creating API Resources for audited models, include the audit relationships:

```php
public function toArray(Request $request): array
{
    return [
        // ... other fields
        'created_by' => $this->whenLoaded('createdBy', fn () => [
            'id' => $this->createdBy->id,
            'name' => $this->createdBy->name,
        ]),
        'updated_by' => $this->whenLoaded('updatedBy', fn () => [
            'id' => $this->updatedBy->id,
            'name' => $this->updatedBy->name,
        ]),
        'previous_updated_by' => $this->whenLoaded('previousUpdatedBy', fn () => [
            'id' => $this->previousUpdatedBy->id,
            'name' => $this->previousUpdatedBy->name,
        ]),
        'previous_updated_at' => $this->previous_updated_at,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

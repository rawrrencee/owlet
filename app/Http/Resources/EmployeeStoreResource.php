<?php

namespace App\Http\Resources;

use App\Constants\StoreAccessPermissions;
use App\Constants\StorePermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeStoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'store_id' => $this->store_id,
            'active' => $this->active,
            'permissions' => $this->permissions ?? [],
            'permissions_with_labels' => $this->getPermissionsWithLabels(),
            'access_permissions' => $this->access_permissions ?? [],
            'access_permissions_with_labels' => $this->getAccessPermissionsWithLabels(),
            'is_creator' => $this->is_creator,
            'store' => $this->whenLoaded('store', fn () => (new StoreResource($this->store))->resolve()),
            'employee' => $this->whenLoaded('employee', fn () => new EmployeeResource($this->employee)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get available permissions for creating/editing.
     */
    public static function availablePermissions(): array
    {
        return StorePermissions::grouped();
    }

    /**
     * Get available access permissions for creating/editing.
     */
    public static function availableAccessPermissions(): array
    {
        return StoreAccessPermissions::grouped();
    }
}

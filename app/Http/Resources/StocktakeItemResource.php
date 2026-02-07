<?php

namespace App\Http\Resources;

use App\Constants\StoreAccessPermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StocktakeItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewDifference = $this->userCanViewDifference($request);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'product_name' => $this->product->product_name,
                'product_number' => $this->product->product_number,
                'variant_name' => $this->product->variant_name,
                'barcode' => $this->product->barcode,
                'image_url' => $this->product->image_path ? route('products.image', $this->product->id) : null,
            ]),
            'counted_quantity' => $this->counted_quantity,
            'has_discrepancy' => $this->has_discrepancy,
            'system_quantity' => $this->when($canViewDifference, fn () => $this->system_quantity),
            'difference' => $this->when($canViewDifference, fn () => $this->difference),
        ];
    }

    protected function userCanViewDifference(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $employee = $user->employee;
        if (! $employee) {
            return false;
        }

        // Get the store_id from the stocktake relation
        $storeId = $this->resource->stocktake?->store_id
            ?? $this->resource->getAttribute('store_id');

        if (! $storeId) {
            return false;
        }

        $employeeStore = $employee->employeeStores()
            ->where('store_id', $storeId)
            ->where('active', true)
            ->first();

        return $employeeStore?->hasAccessPermission(StoreAccessPermissions::STORE_VIEW_STOCKTAKE_DIFFERENCE) ?? false;
    }
}

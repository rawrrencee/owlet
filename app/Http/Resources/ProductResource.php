<?php

namespace App\Http\Resources;

use App\Constants\PagePermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewCostPrice = $this->userCanViewCostPrice($request);

        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'product_number' => $this->product_number,
            'barcode' => $this->barcode,
            'brand_id' => $this->brand_id,
            'brand_name' => $this->whenLoaded('brand', fn () => $this->brand?->brand_name),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'brand_name' => $this->brand->brand_name,
                'brand_code' => $this->brand->brand_code,
            ]),
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->category_name),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'category_name' => $this->category->category_name,
                'category_code' => $this->category->category_code,
            ]),
            'subcategory_id' => $this->subcategory_id,
            'subcategory_name' => $this->whenLoaded('subcategory', fn () => $this->subcategory?->subcategory_name),
            'subcategory' => $this->whenLoaded('subcategory', fn () => [
                'id' => $this->subcategory->id,
                'subcategory_name' => $this->subcategory->subcategory_name,
                'subcategory_code' => $this->subcategory->subcategory_code,
            ]),
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->supplier_name),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'supplier_name' => $this->supplier->supplier_name,
            ]),
            'supplier_number' => $this->supplier_number,
            'description' => $this->description,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->toArray(), []),
            'cost_price_remarks' => $this->when($canViewCostPrice, fn () => $this->cost_price_remarks),
            'image_path' => $this->image_path,
            'image_filename' => $this->image_filename,
            'image_mime_type' => $this->image_mime_type,
            'image_url' => $this->image_path ? route('products.image', $this->id) : null,
            'weight' => $this->weight,
            'weight_unit' => $this->weight_unit?->value,
            'weight_display' => $this->weight ? "{$this->weight} {$this->weight_unit?->value}" : null,
            'is_active' => $this->is_active,
            'is_deleted' => $this->deleted_at !== null,

            // Prices - base prices per currency
            'prices' => $this->whenLoaded('prices', fn () => ProductPriceResource::collection($this->prices)->resolve()),

            // Store assignments with their prices
            'product_stores' => $this->whenLoaded('productStores', fn () => ProductStoreResource::collection($this->productStores)->resolve()),

            // Audit trail
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
            'previous_updated_at' => $this->previous_updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function userCanViewCostPrice(Request $request): bool
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

        $employeePermission = $employee->permission;
        if (! $employeePermission) {
            return false;
        }

        return $employeePermission->hasPermission(PagePermissions::PRODUCTS_VIEW_COST_PRICE);
    }
}

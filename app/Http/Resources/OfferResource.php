<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'discount_type' => $this->discount_type->value,
            'discount_type_label' => $this->discount_type->label(),
            'discount_percentage' => $this->discount_percentage,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_combinable' => $this->is_combinable,
            'priority' => $this->priority,
            'apply_to_all_stores' => $this->apply_to_all_stores,
            'bundle_mode' => $this->bundle_mode?->value,
            'bundle_mode_label' => $this->bundle_mode?->label(),

            // Relations
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'category_name' => $this->category->category_name,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'brand_name' => $this->brand->brand_name,
            ]),
            'stores' => $this->whenLoaded('stores', fn () =>
                $this->stores->map(fn ($store) => [
                    'id' => $store->id,
                    'store_name' => $store->store_name,
                    'store_code' => $store->store_code,
                ])->toArray()
            ),
            'amounts' => $this->whenLoaded('amounts', fn () =>
                $this->amounts->map(fn ($amount) => [
                    'id' => $amount->id,
                    'currency_id' => $amount->currency_id,
                    'currency' => $amount->currency ? [
                        'id' => $amount->currency->id,
                        'code' => $amount->currency->code,
                        'symbol' => $amount->currency->symbol,
                    ] : null,
                    'discount_amount' => $amount->discount_amount,
                    'max_discount_amount' => $amount->max_discount_amount,
                ])->toArray()
            ),
            'products' => $this->whenLoaded('products', fn () =>
                $this->products->map(fn ($op) => [
                    'id' => $op->id,
                    'product_id' => $op->product_id,
                    'product' => $op->product ? [
                        'id' => $op->product->id,
                        'product_name' => $op->product->product_name,
                        'product_number' => $op->product->product_number,
                        'variant_name' => $op->product->variant_name,
                        'barcode' => $op->product->barcode,
                        'image_url' => $op->product->image_path ? route('products.image', $op->product->id) : null,
                    ] : null,
                ])->toArray()
            ),
            'bundles' => $this->whenLoaded('bundles', fn () =>
                $this->bundles->map(fn ($bundle) => [
                    'id' => $bundle->id,
                    'product_id' => $bundle->product_id,
                    'category_id' => $bundle->category_id,
                    'subcategory_id' => $bundle->subcategory_id,
                    'required_quantity' => $bundle->required_quantity,
                    'product' => $bundle->product ? [
                        'id' => $bundle->product->id,
                        'product_name' => $bundle->product->product_name,
                        'product_number' => $bundle->product->product_number,
                        'variant_name' => $bundle->product->variant_name,
                        'barcode' => $bundle->product->barcode,
                        'image_url' => $bundle->product->image_path ? route('products.image', $bundle->product->id) : null,
                    ] : null,
                    'category' => $bundle->category ? [
                        'id' => $bundle->category->id,
                        'category_name' => $bundle->category->category_name,
                    ] : null,
                    'subcategory' => $bundle->subcategory ? [
                        'id' => $bundle->subcategory->id,
                        'subcategory_name' => $bundle->subcategory->subcategory_name,
                        'category' => $bundle->subcategory->category ? [
                            'id' => $bundle->subcategory->category->id,
                            'category_name' => $bundle->subcategory->category->category_name,
                        ] : null,
                    ] : null,
                ])->toArray()
            ),
            'minimum_spends' => $this->whenLoaded('minimumSpends', fn () =>
                $this->minimumSpends->map(fn ($ms) => [
                    'id' => $ms->id,
                    'currency_id' => $ms->currency_id,
                    'currency' => $ms->currency ? [
                        'id' => $ms->currency->id,
                        'code' => $ms->currency->code,
                        'symbol' => $ms->currency->symbol,
                    ] : null,
                    'minimum_amount' => $ms->minimum_amount,
                ])->toArray()
            ),

            // Audit
            'created_by_user' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

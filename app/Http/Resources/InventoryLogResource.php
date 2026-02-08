<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'product_name' => $this->product->product_name,
                'product_number' => $this->product->product_number,
                'variant_name' => $this->product->variant_name,
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'store_name' => $this->store->store_name,
                'store_code' => $this->store->store_code,
            ]),
            'activity_code' => $this->activity_code,
            'activity_label' => $this->activity_label,
            'quantity_in' => $this->quantity_in,
            'quantity_out' => $this->quantity_out,
            'current_quantity' => $this->current_quantity,
            'notes' => $this->notes,
            'stocktake_id' => $this->stocktake_id,
            'delivery_order_id' => $this->delivery_order_id,
            'delivery_order' => $this->whenLoaded('deliveryOrder', fn () => $this->deliveryOrder ? [
                'id' => $this->deliveryOrder->id,
                'order_number' => $this->deliveryOrder->order_number,
            ] : null),
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order' => $this->whenLoaded('purchaseOrder', fn () => $this->purchaseOrder ? [
                'id' => $this->purchaseOrder->id,
                'order_number' => $this->purchaseOrder->order_number,
            ] : null),
            'created_by_user' => $this->whenLoaded('createdByUser', fn () => $this->createdByUser ? [
                'id' => $this->createdByUser->id,
                'name' => $this->createdByUser->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

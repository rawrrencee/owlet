<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'supplier_name' => $this->supplier->supplier_name,
            ]),
            'supplier_id' => $this->supplier_id,
            'store' => $this->whenLoaded('store', fn () => $this->store ? [
                'id' => $this->store->id,
                'store_name' => $this->store->store_name,
                'store_code' => $this->store->store_code,
            ] : null),
            'store_id' => $this->store_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => PurchaseOrderItemResource::collection($this->items)->resolve()),
            'items_count' => $this->whenCounted('items'),
            'has_corrections' => $this->whenLoaded('items', fn () => $this->items->contains(fn ($item) => $item->has_correction)),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'submitted_by_user' => $this->whenLoaded('submittedByUser', fn () => [
                'id' => $this->submittedByUser->id,
                'name' => $this->submittedByUser->name,
            ]),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'resolved_by_user' => $this->whenLoaded('resolvedByUser', fn () => [
                'id' => $this->resolvedByUser->id,
                'name' => $this->resolvedByUser->name,
            ]),
            'rejection_reason' => $this->rejection_reason,
            'created_by_user' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

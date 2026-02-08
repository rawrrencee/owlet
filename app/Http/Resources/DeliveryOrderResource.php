<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'store_from' => $this->whenLoaded('storeFrom', fn () => [
                'id' => $this->storeFrom->id,
                'store_name' => $this->storeFrom->store_name,
                'store_code' => $this->storeFrom->store_code,
            ]),
            'store_to' => $this->whenLoaded('storeTo', fn () => [
                'id' => $this->storeTo->id,
                'store_name' => $this->storeTo->store_name,
                'store_code' => $this->storeTo->store_code,
            ]),
            'store_id_from' => $this->store_id_from,
            'store_id_to' => $this->store_id_to,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => DeliveryOrderItemResource::collection($this->items)->resolve()),
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

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StocktakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->full_name,
                'employee_number' => $this->employee->employee_number,
                'profile_picture_url' => $this->employee->getProfilePictureUrl(),
            ]),
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'store_name' => $this->store->store_name,
                'store_code' => $this->store->store_code,
            ]),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'has_issues' => $this->has_issues,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => StocktakeItemResource::collection($this->items)->resolve()),
            'items_count' => $this->whenCounted('items'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

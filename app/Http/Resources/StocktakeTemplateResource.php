<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StocktakeTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->full_name,
            ]),
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'store_name' => $this->store->store_name,
                'store_code' => $this->store->store_code,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'products' => $this->whenLoaded('products', fn () => $this->products
                ->filter(fn ($p) => $p->is_active && $p->deleted_at === null)
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'product_name' => $p->product_name,
                    'product_number' => $p->product_number,
                    'variant_name' => $p->variant_name,
                ])->values()),
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

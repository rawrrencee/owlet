<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'name' => $this->currency->name,
                'symbol' => $this->currency->symbol,
            ]),
            'unit_cost' => $this->unit_cost,
            'quantity' => $this->quantity,
            'total_cost' => $this->total_cost,
            'received_quantity' => $this->received_quantity,
            'correction_note' => $this->correction_note,
            'has_correction' => $this->has_correction,
        ];
    }
}

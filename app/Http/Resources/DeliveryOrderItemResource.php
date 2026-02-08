<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderItemResource extends JsonResource
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
            'quantity' => $this->quantity,
            'received_quantity' => $this->received_quantity,
            'correction_note' => $this->correction_note,
            'has_correction' => $this->has_correction,
        ];
    }
}

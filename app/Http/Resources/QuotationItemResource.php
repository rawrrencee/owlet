<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id,
                'product_name' => $this->product->product_name,
                'product_number' => $this->product->product_number,
                'variant_name' => $this->product->variant_name,
                'barcode' => $this->product->barcode,
                'image_url' => $this->product->image_path ? route('products.image', $this->product->id) : null,
            ] : null),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => $this->currency ? [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'symbol' => $this->currency->symbol,
            ] : null),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'sort_order' => $this->sort_order,
            'offer_id' => $this->offer_id,
            'offer_name' => $this->offer_name,
            'offer_discount_type' => $this->offer_discount_type,
            'offer_discount_amount' => $this->offer_discount_amount,
            'offer_is_combinable' => $this->offer_is_combinable,
            'customer_discount_percentage' => $this->customer_discount_percentage,
            'customer_discount_amount' => $this->customer_discount_amount,
            'line_subtotal' => $this->line_subtotal,
            'line_discount' => $this->line_discount,
            'line_total' => $this->line_total,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    protected $fillable = [
        'delivery_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'correction_note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'received_quantity' => 'integer',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function hasCorrection(): Attribute
    {
        return Attribute::get(fn () => $this->received_quantity !== null && $this->received_quantity !== $this->quantity);
    }
}

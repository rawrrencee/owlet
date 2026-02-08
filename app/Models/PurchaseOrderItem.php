<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'currency_id',
        'unit_cost',
        'quantity',
        'received_quantity',
        'correction_note',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:4',
            'quantity' => 'integer',
            'received_quantity' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    protected function hasCorrection(): Attribute
    {
        return Attribute::get(fn () => $this->received_quantity !== null && $this->received_quantity !== $this->quantity);
    }

    protected function totalCost(): Attribute
    {
        return Attribute::get(fn () => bcmul($this->unit_cost, (string) $this->quantity, 4));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'product_number',
        'variant_name',
        'barcode',
        'quantity',
        'cost_price',
        'unit_price',
        'offer_id',
        'offer_name',
        'offer_discount_type',
        'offer_discount_amount',
        'offer_is_combinable',
        'customer_discount_percentage',
        'customer_discount_amount',
        'line_subtotal',
        'line_discount',
        'line_total',
        'is_refunded',
        'refund_reason',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'cost_price' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'offer_discount_amount' => 'decimal:4',
            'offer_is_combinable' => 'boolean',
            'customer_discount_percentage' => 'decimal:2',
            'customer_discount_amount' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'line_discount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'is_refunded' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}

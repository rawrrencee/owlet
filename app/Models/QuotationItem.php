<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'product_id',
        'currency_id',
        'quantity',
        'unit_price',
        'sort_order',
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
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:4',
            'sort_order' => 'integer',
            'offer_discount_amount' => 'decimal:4',
            'offer_is_combinable' => 'boolean',
            'customer_discount_percentage' => 'decimal:2',
            'customer_discount_amount' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'line_discount' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferAmount extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'offer_id',
        'currency_id',
        'discount_amount',
        'max_discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:4',
            'max_discount_amount' => 'decimal:4',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

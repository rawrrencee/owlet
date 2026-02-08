<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferMinimumSpend extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'offer_id',
        'currency_id',
        'minimum_amount',
    ];

    protected function casts(): array
    {
        return [
            'minimum_amount' => 'decimal:4',
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

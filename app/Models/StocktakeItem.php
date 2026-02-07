<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StocktakeItem extends Model
{
    protected $fillable = [
        'stocktake_id',
        'product_id',
        'system_quantity',
        'counted_quantity',
        'has_discrepancy',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'integer',
            'counted_quantity' => 'integer',
            'has_discrepancy' => 'boolean',
        ];
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getDifferenceAttribute(): int
    {
        return $this->counted_quantity - $this->system_quantity;
    }
}

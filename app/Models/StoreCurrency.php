<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCurrency extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'currency_id',
        'is_default',
        'exchange_rate',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'exchange_rate' => 'decimal:6',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

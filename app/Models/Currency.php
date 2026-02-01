<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'active',
        'exchange_rate',
        'exchange_rate_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'active' => 'boolean',
            'exchange_rate' => 'decimal:10',
            'exchange_rate_updated_at' => 'datetime',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_currencies')
            ->withTimestamps();
    }

    public function storeCurrencies(): HasMany
    {
        return $this->hasMany(StoreCurrency::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

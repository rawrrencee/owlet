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
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_currencies')
            ->withPivot(['is_default', 'exchange_rate'])
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

<?php

namespace App\Models;

use App\Models\Concerns\TouchesProductAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory, TouchesProductAuditTrail;

    protected $fillable = [
        'product_id',
        'currency_id',
        'cost_price',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

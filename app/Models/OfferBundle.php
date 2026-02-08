<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferBundle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'offer_id',
        'product_id',
        'category_id',
        'subcategory_id',
        'required_quantity',
    ];

    protected function casts(): array
    {
        return [
            'required_quantity' => 'integer',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function isProductEntry(): bool
    {
        return $this->product_id !== null;
    }

    public function isCategoryEntry(): bool
    {
        return $this->category_id !== null;
    }

    public function isSubcategoryEntry(): bool
    {
        return $this->subcategory_id !== null;
    }
}

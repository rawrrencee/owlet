<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StocktakeTemplateItem extends Model
{
    protected $fillable = [
        'template_id',
        'product_id',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(StocktakeTemplate::class, 'template_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

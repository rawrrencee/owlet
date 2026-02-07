<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StocktakeTemplate extends Model
{
    protected $fillable = [
        'employee_id',
        'store_id',
        'name',
        'description',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StocktakeTemplateItem::class, 'template_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'stocktake_template_items', 'template_id', 'product_id')
            ->withTimestamps();
    }
}

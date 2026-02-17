<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavouriteProduct extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'product_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (FavouriteProduct $model) {
            $model->created_at = now();
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\TouchesProductAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use TouchesProductAuditTrail;

    protected $fillable = [
        'product_id',
        'image_path',
        'image_filename',
        'image_mime_type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

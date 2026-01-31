<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'subcategory_name',
        'subcategory_code',
        'description',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mutator to ensure subcategory_code is always uppercase.
     */
    public function setSubcategoryCodeAttribute(string $value): void
    {
        $this->attributes['subcategory_code'] = strtoupper($value);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

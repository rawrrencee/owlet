<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_name',
        'category_code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mutator to ensure category_code is always uppercase.
     */
    public function setCategoryCodeAttribute(string $value): void
    {
        $this->attributes['category_code'] = strtoupper($value);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function activeSubcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class)->where('is_active', true);
    }

    public function defaultSubcategory(): HasMany
    {
        return $this->hasMany(Subcategory::class)->where('is_default', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Mutator to ensure tag name is always lowercase and trimmed.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = strtolower(trim($value));
    }

    /**
     * Get the products that have this tag.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Find or create a tag by name.
     */
    public static function findOrCreateByName(string $name): self
    {
        $normalizedName = strtolower(trim($name));

        return self::firstOrCreate(['name' => $normalizedName]);
    }

    /**
     * Find or create multiple tags by names.
     *
     * @param  array<string>  $names
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function findOrCreateByNames(array $names): \Illuminate\Support\Collection
    {
        return collect($names)
            ->filter()
            ->map(fn ($name) => self::findOrCreateByName($name));
    }
}

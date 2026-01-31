<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasAuditTrail, HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_name',
        'brand_code',
        'country_id',
        'address_1',
        'address_2',
        'email',
        'phone_number',
        'mobile_number',
        'website',
        'logo_path',
        'logo_filename',
        'logo_mime_type',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mutator to ensure brand_code is always uppercase.
     */
    public function setBrandCodeAttribute(string $value): void
    {
        $this->attributes['brand_code'] = strtoupper($value);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}

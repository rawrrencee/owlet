<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'quotation_number',
        'company_id',
        'customer_id',
        'status',
        'show_company_logo',
        'show_company_address',
        'show_company_uen',
        'tax_mode',
        'tax_store_id',
        'tax_percentage',
        'tax_inclusive',
        'terms_and_conditions',
        'internal_notes',
        'external_notes',
        'payment_terms',
        'validity_date',
        'customer_discount_percentage',
        'share_token',
        'share_password_hash',
        'signed_at',
        'signature_data',
        'signed_by_name',
        'signed_by_ip',
        'sent_at',
        'viewed_at',
        'expired_at',
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'show_company_logo' => 'boolean',
            'show_company_address' => 'boolean',
            'show_company_uen' => 'boolean',
            'tax_percentage' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'customer_discount_percentage' => 'decimal:2',
            'validity_date' => 'date',
            'signed_at' => 'datetime',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function taxStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'tax_store_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function paymentModes(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMode::class, 'quotation_payment_mode');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('quotation_number', 'like', "%{$search}%");
        });
    }

    public function scopeDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    public static function generateQuotationNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "QT-{$date}-";

        $latest = static::withTrashed()
            ->where('quotation_number', 'like', "{$prefix}%")
            ->orderByDesc('quotation_number')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->quotation_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}

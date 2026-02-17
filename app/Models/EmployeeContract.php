<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeContract extends Model
{
    use HasAuditTrail, HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'start_date',
        'end_date',
        'salary_amount',
        'external_document_url',
        'document_path',
        'document_filename',
        'document_mime_type',
        'comments',
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'salary_amount' => 'decimal:4',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveEntitlements(): HasMany
    {
        return $this->hasMany(ContractLeaveEntitlement::class);
    }

    public function isActive(): bool
    {
        $today = now()->startOfDay();

        if ($this->end_date === null) {
            return $this->start_date <= $today;
        }

        return $this->start_date <= $today && $this->end_date >= $today;
    }

    public function hasDocument(): bool
    {
        return $this->document_path !== null || $this->external_document_url !== null;
    }

    public function isDocumentViewableInline(): bool
    {
        if ($this->external_document_url) {
            return false;
        }

        if (! $this->document_mime_type) {
            return false;
        }

        return str_starts_with($this->document_mime_type, 'image/');
    }
}

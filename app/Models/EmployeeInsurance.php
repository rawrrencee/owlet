<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'title',
        'insurer_name',
        'policy_number',
        'start_date',
        'end_date',
        'external_document_url',
        'document_path',
        'document_filename',
        'document_mime_type',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

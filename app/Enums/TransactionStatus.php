<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case DRAFT = 'draft';
    case SUSPENDED = 'suspended';
    case COMPLETED = 'completed';
    case VOIDED = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUSPENDED => 'Suspended',
            self::COMPLETED => 'Completed',
            self::VOIDED => 'Voided',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::DRAFT => 'info',
            self::SUSPENDED => 'warn',
            self::COMPLETED => 'success',
            self::VOIDED => 'danger',
        };
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}

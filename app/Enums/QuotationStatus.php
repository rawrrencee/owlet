<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case VIEWED = 'viewed';
    case SIGNED = 'signed';
    case ACCEPTED = 'accepted';
    case PAID = 'paid';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::VIEWED => 'Viewed',
            self::SIGNED => 'Signed',
            self::ACCEPTED => 'Accepted',
            self::PAID => 'Paid',
            self::EXPIRED => 'Expired',
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

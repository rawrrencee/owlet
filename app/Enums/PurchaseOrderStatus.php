<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Get all statuses as options for selects.
     *
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

<?php

namespace App\Enums;

enum StocktakeStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'In Progress',
            self::SUBMITTED => 'Submitted',
            self::EXPIRED => 'Expired',
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

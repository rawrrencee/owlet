<?php

namespace App\Enums;

enum BundleMode: string
{
    case WHOLE = 'whole';
    case CHEAPEST_ITEM = 'cheapest_item';

    public function label(): string
    {
        return match ($this) {
            self::WHOLE => 'Discount on Entire Bundle',
            self::CHEAPEST_ITEM => 'Discount on Cheapest Item',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $mode) => [
                'value' => $mode->value,
                'label' => $mode->label(),
            ],
            self::cases()
        );
    }
}

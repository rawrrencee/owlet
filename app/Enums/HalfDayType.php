<?php

namespace App\Enums;

enum HalfDayType: string
{
    case FULL = 'full';
    case AM = 'am';
    case PM = 'pm';

    public function label(): string
    {
        return match ($this) {
            self::FULL => 'Full Day',
            self::AM => 'AM (Morning)',
            self::PM => 'PM (Afternoon)',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}

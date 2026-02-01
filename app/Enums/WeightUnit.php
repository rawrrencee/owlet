<?php

namespace App\Enums;

enum WeightUnit: string
{
    case KILOGRAM = 'kg';
    case GRAM = 'g';
    case POUND = 'lb';
    case OUNCE = 'oz';

    public function label(): string
    {
        return match ($this) {
            self::KILOGRAM => 'Kilogram (kg)',
            self::GRAM => 'Gram (g)',
            self::POUND => 'Pound (lb)',
            self::OUNCE => 'Ounce (oz)',
        };
    }

    public function abbreviation(): string
    {
        return $this->value;
    }

    /**
     * Get all weight units as options for selects.
     *
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $unit) => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ],
            self::cases()
        );
    }
}

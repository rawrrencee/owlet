<?php

namespace App\Constants;

class InventoryActivityCodes
{
    public const LOST_ITEM = 'LI';

    public const FOUND_ITEM = 'FI';

    /**
     * Get all activity codes with their labels.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            self::LOST_ITEM => 'Lost Item',
            self::FOUND_ITEM => 'Found Item',
        ];
    }

    /**
     * Get the label for a given activity code.
     */
    public static function label(string $code): string
    {
        return self::all()[$code] ?? $code;
    }
}

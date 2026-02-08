<?php

namespace App\Constants;

class InventoryActivityCodes
{
    public const LOST_ITEM = 'LI';

    public const FOUND_ITEM = 'FI';

    public const DELIVERY_ORDER_OUT = 'DO';

    public const DELIVERY_ORDER_IN = 'DI';

    public const PURCHASE_ORDER_IN = 'PI';

    public const DELIVERY_ORDER_REVERT_IN = 'DRI';

    public const DELIVERY_ORDER_REVERT_OUT = 'DRO';

    public const PURCHASE_ORDER_REVERT_OUT = 'PRO';

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
            self::DELIVERY_ORDER_OUT => 'Delivery Out',
            self::DELIVERY_ORDER_IN => 'Delivery In',
            self::PURCHASE_ORDER_IN => 'Purchase In',
            self::DELIVERY_ORDER_REVERT_IN => 'Delivery Revert In',
            self::DELIVERY_ORDER_REVERT_OUT => 'Delivery Revert Out',
            self::PURCHASE_ORDER_REVERT_OUT => 'Purchase Revert Out',
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

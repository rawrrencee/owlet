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

    public const SOLD_ITEM = 'SI';

    public const REFUND_ITEM = 'RI';

    public const TRANSACTION_ADJUSTMENT = 'TA';

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
            self::SOLD_ITEM => 'Sold Item',
            self::REFUND_ITEM => 'Refund Item',
            self::TRANSACTION_ADJUSTMENT => 'Transaction Adjustment',
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

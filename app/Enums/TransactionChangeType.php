<?php

namespace App\Enums;

enum TransactionChangeType: string
{
    case CREATED = 'created';
    case ITEM_ADDED = 'item_added';
    case ITEM_REMOVED = 'item_removed';
    case ITEM_MODIFIED = 'item_modified';
    case CUSTOMER_CHANGED = 'customer_changed';
    case PAYMENT_ADDED = 'payment_added';
    case PAYMENT_REMOVED = 'payment_removed';
    case DISCOUNT_APPLIED = 'discount_applied';
    case OFFER_APPLIED = 'offer_applied';
    case COMPLETED = 'completed';
    case SUSPENDED = 'suspended';
    case RESUMED = 'resumed';
    case VOIDED = 'voided';
    case REFUND = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::ITEM_ADDED => 'Item Added',
            self::ITEM_REMOVED => 'Item Removed',
            self::ITEM_MODIFIED => 'Item Modified',
            self::CUSTOMER_CHANGED => 'Customer Changed',
            self::PAYMENT_ADDED => 'Payment Added',
            self::PAYMENT_REMOVED => 'Payment Removed',
            self::DISCOUNT_APPLIED => 'Discount Applied',
            self::OFFER_APPLIED => 'Offer Applied',
            self::COMPLETED => 'Completed',
            self::SUSPENDED => 'Suspended',
            self::RESUMED => 'Resumed',
            self::VOIDED => 'Voided',
            self::REFUND => 'Refund',
        };
    }
}

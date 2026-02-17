<?php

namespace App\Enums;

enum NotificationEventType: string
{
    case Stocktake = 'stocktake';
    case Transaction = 'transaction';
    case Refund = 'refund';
    case AmendedTransaction = 'amended_transaction';
    case DeliveryOrder = 'delivery_order';
    case PurchaseOrder = 'purchase_order';
    case Quotation = 'quotation';
    case Timecard = 'timecard';

    public function label(): string
    {
        return match ($this) {
            self::Stocktake => 'Stocktake',
            self::Transaction => 'Transaction',
            self::Refund => 'Refund',
            self::AmendedTransaction => 'Amended Transaction',
            self::DeliveryOrder => 'Delivery Order',
            self::PurchaseOrder => 'Purchase Order',
            self::Quotation => 'Quotation',
            self::Timecard => 'Timecard',
        };
    }
}

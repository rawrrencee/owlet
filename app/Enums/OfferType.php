<?php

namespace App\Enums;

enum OfferType: string
{
    case PRODUCT = 'product';
    case BUNDLE = 'bundle';
    case MINIMUM_SPEND = 'minimum_spend';
    case CATEGORY = 'category';
    case BRAND = 'brand';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT => 'Product',
            self::BUNDLE => 'Bundle',
            self::MINIMUM_SPEND => 'Minimum Spend',
            self::CATEGORY => 'Category',
            self::BRAND => 'Brand',
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

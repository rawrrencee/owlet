<?php

namespace Database\Seeders;

use App\Services\CurrencyService;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $fallbackCurrencies = [
            // Priority Asian currencies
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimal_places' => 2],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimal_places' => 2],

            // Other Asian currencies
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => "\u{00A5}", 'decimal_places' => 0],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => "\u{00A5}", 'decimal_places' => 2],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'decimal_places' => 2],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => "\u{20A9}", 'decimal_places' => 0],
            ['code' => 'TWD', 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$', 'decimal_places' => 2],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => "\u{0E3F}", 'decimal_places' => 2],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimal_places' => 0],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => "\u{20B1}", 'decimal_places' => 2],
            ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => "\u{20AB}", 'decimal_places' => 0],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => "\u{20B9}", 'decimal_places' => 2],

            // Oceania
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'decimal_places' => 2],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'decimal_places' => 2],

            // North America
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$', 'decimal_places' => 2],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$', 'decimal_places' => 2],

            // Europe
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => "\u{20AC}", 'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => "\u{00A3}", 'decimal_places' => 2],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'decimal_places' => 2],
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr', 'decimal_places' => 2],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr', 'decimal_places' => 2],
            ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr', 'decimal_places' => 2],
            ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'z\u{0142}', 'decimal_places' => 2],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => "\u{20BA}", 'decimal_places' => 2],

            // Middle East & Africa
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'DH', 'decimal_places' => 2],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SR', 'decimal_places' => 2],
            ['code' => 'ILS', 'name' => 'Israeli New Shekel', 'symbol' => "\u{20AA}", 'decimal_places' => 2],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'decimal_places' => 2],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E\u{00A3}', 'decimal_places' => 2],

            // South America
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$', 'decimal_places' => 2],
            ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => '$', 'decimal_places' => 0],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$', 'decimal_places' => 0],
        ];

        /** @var CurrencyService $currencyService */
        $currencyService = app(CurrencyService::class);
        $result = $currencyService->syncCurrenciesFromApi($fallbackCurrencies);

        $source = $result['from_api'] ? 'API' : 'fallback data';
        $this->command?->info("Synced {$result['synced']} currencies from {$source}");
    }
}

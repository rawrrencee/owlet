<?php

namespace App\Services;

use App\Contracts\CurrencyFetcherInterface;
use App\Models\Currency;
use Illuminate\Support\Carbon;

class CurrencyService
{
    public function __construct(
        protected CurrencyFetcherInterface $currencyFetcher
    ) {}

    /**
     * Refresh exchange rates for all currencies in the database.
     *
     * @return array{updated: int, failed: int, base_currency: string}
     */
    public function refreshExchangeRates(): array
    {
        $rates = $this->currencyFetcher->fetchExchangeRates();
        $baseCurrency = $this->currencyFetcher->getBaseCurrency();

        if ($rates->isEmpty()) {
            return [
                'updated' => 0,
                'failed' => Currency::count(),
                'base_currency' => $baseCurrency,
            ];
        }

        $updated = 0;
        $failed = 0;
        $now = Carbon::now();

        // Get all currencies from database
        $currencies = Currency::all();

        foreach ($currencies as $currency) {
            $code = strtoupper($currency->code);

            // Base currency always has rate of 1.0
            if ($code === $baseCurrency) {
                $currency->update([
                    'exchange_rate' => 1.0,
                    'exchange_rate_updated_at' => $now,
                ]);
                $updated++;

                continue;
            }

            // Check if we have a rate for this currency
            if ($rates->has($code)) {
                $currency->update([
                    'exchange_rate' => $rates->get($code),
                    'exchange_rate_updated_at' => $now,
                ]);
                $updated++;
            } else {
                $failed++;
            }
        }

        return [
            'updated' => $updated,
            'failed' => $failed,
            'base_currency' => $baseCurrency,
        ];
    }

    /**
     * Sync currencies from the API, used by the seeder.
     * Creates or updates currencies based on API data.
     *
     * @param  array<array{code: string, name: string, symbol: string, decimal_places: int}>  $fallbackCurrencies
     * @return array{synced: int, from_api: bool}
     */
    public function syncCurrenciesFromApi(array $fallbackCurrencies = []): array
    {
        $apiCurrencies = $this->currencyFetcher->fetchCurrencies();
        $rates = $this->currencyFetcher->fetchExchangeRates();
        $baseCurrency = $this->currencyFetcher->getBaseCurrency();

        // If API fails, use fallback currencies
        if ($apiCurrencies->isEmpty()) {
            foreach ($fallbackCurrencies as $currencyData) {
                Currency::firstOrCreate(
                    ['code' => $currencyData['code']],
                    $currencyData
                );
            }

            return [
                'synced' => count($fallbackCurrencies),
                'from_api' => false,
            ];
        }

        $synced = 0;
        $now = Carbon::now();

        // Only sync currencies that exist in our fallback list (we have symbol info for these)
        foreach ($fallbackCurrencies as $currencyData) {
            $code = strtoupper($currencyData['code']);

            // Try to get the name from API, fallback to our name
            $name = $apiCurrencies->get($code, $currencyData['name']);

            // Get exchange rate from API
            $exchangeRate = null;
            $exchangeRateUpdatedAt = null;

            if ($code === $baseCurrency) {
                $exchangeRate = 1.0;
                $exchangeRateUpdatedAt = $now;
            } elseif ($rates->has($code)) {
                $exchangeRate = $rates->get($code);
                $exchangeRateUpdatedAt = $now;
            }

            Currency::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'symbol' => $currencyData['symbol'],
                    'decimal_places' => $currencyData['decimal_places'],
                    'exchange_rate' => $exchangeRate,
                    'exchange_rate_updated_at' => $exchangeRateUpdatedAt,
                ]
            );

            $synced++;
        }

        return [
            'synced' => $synced,
            'from_api' => true,
        ];
    }
}

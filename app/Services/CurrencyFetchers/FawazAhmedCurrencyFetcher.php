<?php

namespace App\Services\CurrencyFetchers;

use App\Contracts\CurrencyFetcherInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FawazAhmedCurrencyFetcher implements CurrencyFetcherInterface
{
    private const BASE_CURRENCY = 'SGD';

    private const CURRENCIES_URL = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies.json';

    private const EXCHANGE_RATES_URL = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/sgd.json';

    public function fetchCurrencies(): Collection
    {
        try {
            $response = Http::timeout(30)->get(self::CURRENCIES_URL);

            if (! $response->successful()) {
                Log::warning('Failed to fetch currencies from API', [
                    'status' => $response->status(),
                    'url' => self::CURRENCIES_URL,
                ]);

                return collect();
            }

            return collect($response->json())
                ->filter(fn ($name) => is_string($name) && ! empty($name))
                ->mapWithKeys(fn ($name, $code) => [strtoupper($code) => $name]);
        } catch (\Exception $e) {
            Log::error('Exception while fetching currencies', [
                'message' => $e->getMessage(),
                'url' => self::CURRENCIES_URL,
            ]);

            return collect();
        }
    }

    public function fetchExchangeRates(): Collection
    {
        try {
            $response = Http::timeout(30)->get(self::EXCHANGE_RATES_URL);

            if (! $response->successful()) {
                Log::warning('Failed to fetch exchange rates from API', [
                    'status' => $response->status(),
                    'url' => self::EXCHANGE_RATES_URL,
                ]);

                return collect();
            }

            $data = $response->json();

            // The API returns { "date": "...", "sgd": { "usd": 0.74, ... } }
            $rates = $data['sgd'] ?? [];

            return collect($rates)
                ->filter(fn ($rate) => is_numeric($rate))
                ->mapWithKeys(fn ($rate, $code) => [strtoupper($code) => (float) $rate]);
        } catch (\Exception $e) {
            Log::error('Exception while fetching exchange rates', [
                'message' => $e->getMessage(),
                'url' => self::EXCHANGE_RATES_URL,
            ]);

            return collect();
        }
    }

    public function getBaseCurrency(): string
    {
        return self::BASE_CURRENCY;
    }
}

# Currency Management System

## Overview

The currency management system provides exchange rate fetching from external APIs with a pluggable architecture that allows swapping API providers without changing application code.

## Architecture

### Interface-Based Design

The system uses a contract-based approach with `CurrencyFetcherInterface` that defines the required methods for any exchange rate provider:

```php
interface CurrencyFetcherInterface
{
    public function fetchCurrencies(): Collection;    // Get currency list with names
    public function fetchExchangeRates(): Collection; // Get exchange rates
    public function getBaseCurrency(): string;        // Get base currency code (e.g., "SGD")
}
```

### Current Implementation

The default implementation uses **Fawaz Ahmed's Currency API** (free, no API key required):

- **Currency List:** `https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies.json`
- **Exchange Rates:** `https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/sgd.json`

Located at: `app/Services/CurrencyFetchers/FawazAhmedCurrencyFetcher.php`

### Service Binding

The interface is bound to the implementation in `AppServiceProvider`:

```php
public function register(): void
{
    $this->app->bind(CurrencyFetcherInterface::class, FawazAhmedCurrencyFetcher::class);
}
```

## Swapping to Another API Provider

### Step 1: Create a New Fetcher Class

Create a new class in `app/Services/CurrencyFetchers/` that implements `CurrencyFetcherInterface`:

```php
<?php

namespace App\Services\CurrencyFetchers;

use App\Contracts\CurrencyFetcherInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ExchangeRateApiCurrencyFetcher implements CurrencyFetcherInterface
{
    private const BASE_CURRENCY = 'SGD';
    private const API_KEY = 'your-api-key'; // Or use config('services.exchange_rate_api.key')

    public function fetchCurrencies(): Collection
    {
        // Implement API call to fetch currency list
        // Return Collection keyed by currency code with name as value
        // Example: collect(['USD' => 'US Dollar', 'EUR' => 'Euro'])
    }

    public function fetchExchangeRates(): Collection
    {
        // Implement API call to fetch exchange rates
        // Return Collection keyed by currency code with rate as value
        // Example: collect(['USD' => 0.74, 'EUR' => 0.69])
    }

    public function getBaseCurrency(): string
    {
        return self::BASE_CURRENCY;
    }
}
```

### Step 2: Update the Service Provider

Change the binding in `app/Providers/AppServiceProvider.php`:

```php
use App\Services\CurrencyFetchers\ExchangeRateApiCurrencyFetcher;

public function register(): void
{
    $this->app->bind(CurrencyFetcherInterface::class, ExchangeRateApiCurrencyFetcher::class);
}
```

### Step 3: (Optional) Environment-Based Switching

For flexibility, you can switch providers based on configuration:

```php
public function register(): void
{
    $this->app->bind(CurrencyFetcherInterface::class, function () {
        return match (config('services.currency.provider', 'fawaz')) {
            'exchange_rate_api' => new ExchangeRateApiCurrencyFetcher(),
            'open_exchange' => new OpenExchangeRatesFetcher(),
            default => new FawazAhmedCurrencyFetcher(),
        };
    });
}
```

Then in `config/services.php`:

```php
'currency' => [
    'provider' => env('CURRENCY_PROVIDER', 'fawaz'),
],
```

## Key Components

| Component | Location | Purpose |
|-----------|----------|---------|
| Interface | `app/Contracts/CurrencyFetcherInterface.php` | Contract for all fetcher implementations |
| Default Fetcher | `app/Services/CurrencyFetchers/FawazAhmedCurrencyFetcher.php` | Fawaz Ahmed API implementation |
| Currency Service | `app/Services/CurrencyService.php` | Business logic for refreshing rates |
| Controller | `app/Http/Controllers/CurrencyController.php` | CRUD + refresh rates endpoint |

## Usage

### Refreshing Exchange Rates

**Via Admin UI:**
1. Navigate to `/currencies`
2. Click "Refresh Exchange Rates" button

**Via Code:**
```php
use App\Services\CurrencyService;

$service = app(CurrencyService::class);
$result = $service->refreshExchangeRates();
// Returns: ['updated' => 34, 'failed' => 0, 'base_currency' => 'SGD']
```

**Via Artisan (if you create a command):**
```bash
php artisan currencies:refresh
```

### Seeding with API Data

The `CurrencySeeder` automatically fetches exchange rates from the API when seeding:

```bash
php artisan db:seed --class=CurrencySeeder
```

If the API is unavailable, it falls back to hardcoded currency data without exchange rates.

## Error Handling

All fetcher implementations should:
- Return empty collections on API failure (not throw exceptions)
- Log warnings/errors for debugging
- Allow the application to continue functioning with stale data

The `CurrencyService` handles empty results gracefully and reports the number of failed updates.

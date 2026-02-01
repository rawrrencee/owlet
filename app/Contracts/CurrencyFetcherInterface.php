<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface CurrencyFetcherInterface
{
    /**
     * Fetch list of currencies with their names.
     *
     * @return Collection<string, string> Keyed by currency code, values are currency names
     */
    public function fetchCurrencies(): Collection;

    /**
     * Fetch exchange rates relative to the base currency.
     *
     * @return Collection<string, float> Keyed by currency code, values are exchange rates
     */
    public function fetchExchangeRates(): Collection;

    /**
     * Get the base currency code for exchange rates.
     */
    public function getBaseCurrency(): string;
}

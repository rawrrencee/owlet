<?php

namespace App\Data;

class CurrencyData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?float $exchangeRate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: strtoupper($data['code']),
            name: $data['name'],
            exchangeRate: $data['exchange_rate'] ?? null,
        );
    }
}

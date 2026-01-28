<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreCurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'currency_id' => $this->currency_id,
            'is_default' => $this->is_default,
            'exchange_rate' => $this->exchange_rate,
            'currency' => $this->whenLoaded('currency', fn () => (new CurrencyResource($this->currency))->resolve()),
            'store' => $this->whenLoaded('store', fn () => (new StoreResource($this->store))->resolve()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

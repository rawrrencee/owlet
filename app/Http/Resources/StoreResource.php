<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'store_code' => $this->store_code,
            'company_id' => $this->company_id,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'mobile_number' => $this->mobile_number,
            'website' => $this->website,
            'active' => $this->active,
            'include_tax' => $this->include_tax,
            'tax_percentage' => $this->tax_percentage,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? route('stores.logo', $this->id) : null,
            'company' => $this->whenLoaded('company', fn () => (new CompanyResource($this->company))->resolve()),
            'store_currencies' => $this->whenLoaded('storeCurrencies', fn () => StoreCurrencyResource::collection($this->storeCurrencies)->resolve()),
            'default_currency' => $this->whenLoaded('defaultStoreCurrency', function () {
                if ($this->defaultStoreCurrency && $this->defaultStoreCurrency->currency) {
                    return (new CurrencyResource($this->defaultStoreCurrency->currency))->resolve();
                }

                return null;
            }),
            'is_deleted' => $this->deleted_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

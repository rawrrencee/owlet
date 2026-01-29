<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'country_id' => $this->country_id,
            'country_name' => $this->whenLoaded('country', fn () => $this->country?->name),
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'mobile_number' => $this->mobile_number,
            'website' => $this->website,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? route('companies.logo', $this->id) : null,
            'active' => $this->active,
            'is_deleted' => $this->deleted_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

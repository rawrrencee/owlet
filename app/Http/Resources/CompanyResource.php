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
            'registration_number' => $this->registration_number,
            'tax_registration_number' => $this->tax_registration_number,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country_id' => $this->country_id,
            'country_name' => $this->whenLoaded('country', fn () => $this->country?->name),
            'country_code' => $this->whenLoaded('country', fn () => $this->country?->code),
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'mobile_number' => $this->mobile_number,
            'website' => $this->website,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? route('companies.logo', $this->id) : null,
            'active' => $this->active,
            'is_deleted' => $this->deleted_at !== null,
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'updated_by' => $this->whenLoaded('updatedBy', fn () => [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
            ]),
            'previous_updated_by' => $this->whenLoaded('previousUpdatedBy', fn () => [
                'id' => $this->previousUpdatedBy->id,
                'name' => $this->previousUpdatedBy->name,
            ]),
            'previous_updated_at' => $this->previous_updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

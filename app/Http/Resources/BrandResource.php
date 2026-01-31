<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_name' => $this->brand_name,
            'brand_code' => $this->brand_code,
            'country_id' => $this->country_id,
            'country_name' => $this->whenLoaded('country', fn () => $this->country?->name),
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'mobile_number' => $this->mobile_number,
            'website' => $this->website,
            'logo_path' => $this->logo_path,
            'logo_filename' => $this->logo_filename,
            'logo_mime_type' => $this->logo_mime_type,
            'logo_url' => $this->logo_path ? route('brands.logo', $this->id) : null,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_deleted' => $this->deleted_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

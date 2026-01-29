<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'code_3' => $this->code_3,
            'nationality_name' => $this->nationality_name,
            'phone_code' => $this->phone_code,
            'active' => $this->active,
            'sort_order' => $this->sort_order,
        ];
    }
}

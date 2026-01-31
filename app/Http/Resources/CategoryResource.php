<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_name' => $this->category_name,
            'category_code' => $this->category_code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_deleted' => $this->deleted_at !== null,
            'subcategories' => $this->whenLoaded('subcategories', fn () => SubcategoryResource::collection($this->subcategories)->resolve()),
            'subcategories_count' => $this->whenCounted('subcategories'),
            'active_subcategories_count' => $this->whenCounted('activeSubcategories'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\DataMigration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MigrationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'model_type' => $this->model_type,
            'legacy_id' => $this->legacy_id,
            'owlet_id' => $this->owlet_id,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

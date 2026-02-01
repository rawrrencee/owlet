<?php

namespace App\DataMigration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MigrationRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'model_type' => $this->model_type,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'total_records' => $this->total_records,
            'migrated_count' => $this->migrated_count,
            'failed_count' => $this->failed_count,
            'skipped_count' => $this->skipped_count,
            'error_message' => $this->error_message,
            'progress_percentage' => $this->getProgressPercentage(),
            'initiated_by' => $this->initiatedBy?->name ?? 'System',
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

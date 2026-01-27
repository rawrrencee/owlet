<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimecardDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'timecard_id' => $this->timecard_id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'hours' => (float) $this->hours,
            'is_work' => $this->is_work,
            'is_break' => $this->is_break,
            'is_open' => $this->is_open,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

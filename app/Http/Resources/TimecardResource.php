<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimecardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'store_id' => $this->store_id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'hours_worked' => (float) $this->hours_worked,
            'is_in_progress' => $this->is_in_progress,
            'is_completed' => $this->is_completed,
            'is_expired' => $this->is_expired,
            'is_on_break' => $this->isOnBreak(),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->full_name,
                'employee_number' => $this->employee->employee_number,
                'profile_picture_url' => $this->employee->getProfilePictureUrl(),
            ]),
            'store' => $this->store ? [
                'id' => $this->store->id,
                'name' => $this->store->store_name,
                'store_code' => $this->store->store_code,
            ] : null,
            'details' => $this->whenLoaded('details', fn () => TimecardDetailResource::collection($this->details)->resolve()),
            'current_detail' => $this->when(
                $this->relationLoaded('details'),
                fn () => $this->getCurrentDetail() ? (new TimecardDetailResource($this->getCurrentDetail()))->resolve() : null
            ),
            'created_by' => $this->whenLoaded('createdByEmployee', fn () => [
                'id' => $this->createdByEmployee->id,
                'name' => $this->createdByEmployee->full_name,
            ]),
            'updated_by' => $this->whenLoaded('updatedByEmployee', fn () => [
                'id' => $this->updatedByEmployee->id,
                'name' => $this->updatedByEmployee->full_name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_contract_id' => $this->employee_contract_id,
            'leave_type_id' => $this->leave_type_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'start_half_day' => $this->start_half_day?->value ?? $this->start_half_day,
            'end_half_day' => $this->end_half_day?->value ?? $this->end_half_day,
            'total_days' => (float) $this->total_days,
            'reason' => $this->reason,
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status instanceof \App\Enums\LeaveRequestStatus
                ? $this->status->label()
                : ucfirst($this->status),
            'rejection_reason' => $this->rejection_reason,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'first_name' => $this->employee->first_name,
                'last_name' => $this->employee->last_name,
                'full_name' => $this->employee->full_name,
                'employee_number' => $this->employee->employee_number,
                'profile_picture_url' => $this->employee->getProfilePictureUrl(),
            ]),
            'leave_type' => $this->whenLoaded('leaveType', fn () => (new LeaveTypeResource($this->leaveType))->resolve()),
            'resolved_by' => $this->whenLoaded('resolvedByUser', fn () => $this->resolvedByUser ? [
                'id' => $this->resolvedByUser->id,
                'name' => $this->resolvedByUser->name,
            ] : null),
            'cancelled_by' => $this->whenLoaded('cancelledByUser', fn () => $this->cancelledByUser ? [
                'id' => $this->cancelledByUser->id,
                'name' => $this->cancelledByUser->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

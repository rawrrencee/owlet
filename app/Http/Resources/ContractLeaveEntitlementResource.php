<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractLeaveEntitlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_contract_id' => $this->employee_contract_id,
            'leave_type_id' => $this->leave_type_id,
            'entitled_days' => (float) $this->entitled_days,
            'taken_days' => (float) $this->taken_days,
            'remaining_days' => $this->remaining_days,
            'leave_type' => $this->whenLoaded('leaveType', fn () => (new LeaveTypeResource($this->leaveType))->resolve()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeCompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'company_id' => $this->company_id,
            'designation_id' => $this->designation_id,
            'levy_amount' => $this->levy_amount,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'include_shg_donations' => $this->include_shg_donations,
            'commencement_date' => $this->commencement_date?->toDateString(),
            'left_date' => $this->left_date?->toDateString(),
            'is_active' => $this->isActive(),
            'company' => $this->company ? (new CompanyResource($this->company))->resolve() : null,
            'designation' => $this->designation ? (new DesignationResource($this->designation))->resolve() : null,
            'employee' => $this->whenLoaded('employee', fn () => new EmployeeResource($this->employee)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'chinese_name' => $this->chinese_name,
            'employee_number' => $this->employee_number,
            'nric' => $this->nric,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'country_id' => $this->country_id,
            'country_name' => $this->whenLoaded('countryOfResidence', fn () => $this->countryOfResidence?->name),
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'race' => $this->race,
            'nationality' => $this->nationality,
            'nationality_id' => $this->nationality_id,
            'nationality_name' => $this->whenLoaded('nationalityCountry', fn () => $this->nationalityCountry?->nationality_name),
            'residency_status' => $this->residency_status,
            'pr_conversion_date' => $this->pr_conversion_date?->toDateString(),
            'emergency_name' => $this->emergency_name,
            'emergency_relationship' => $this->emergency_relationship,
            'emergency_contact' => $this->emergency_contact,
            'emergency_address_1' => $this->emergency_address_1,
            'emergency_address_2' => $this->emergency_address_2,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'hire_date' => $this->hire_date?->toDateString(),
            'termination_date' => $this->termination_date?->toDateString(),
            'notes' => $this->notes,
            'profile_picture_url' => $this->getProfilePictureUrl(),
            'is_active' => $this->isActive(),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'employee_companies' => $this->whenLoaded('employeeCompanies', fn () => EmployeeCompanyResource::collection($this->employeeCompanies)),
            'companies' => $this->whenLoaded('employeeCompanies', fn () => $this->employeeCompanies
                ->filter(fn ($ec) => $ec->company && $ec->isActive())
                ->map(fn ($ec) => [
                    'id' => $ec->company->id,
                    'name' => $ec->company->company_name,
                ])
                ->values()
                ->all()),
            'is_deleted' => $this->trashed(),
            'pending_email' => $this->pending_email,
            'pending_role' => $this->pending_role,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'chinese_name' => $this->chinese_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'race' => $this->race,
            'nric' => $this->nric,
            'nationality' => $this->nationality,
            'nationality_id' => $this->nationality_id,
            'nationality_name' => $this->nationalityCountry?->nationality_name,
            'residency_status' => $this->residency_status,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country_id' => $this->country_id,
            'country_name' => $this->country?->name,
            'emergency_name' => $this->emergency_name,
            'emergency_relationship' => $this->emergency_relationship,
            'emergency_contact' => $this->emergency_contact,
            'emergency_address_1' => $this->emergency_address_1,
            'emergency_address_2' => $this->emergency_address_2,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'notes' => $this->notes,
            'profile_picture' => $this->profile_picture,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_severity' => $this->status->severity(),
            'rejection_reason' => $this->rejection_reason,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'approved_by_name' => $this->approvedByUser?->name,
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'rejected_by_name' => $this->rejectedByUser?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

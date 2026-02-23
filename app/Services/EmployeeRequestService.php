<?php

namespace App\Services;

use App\Enums\EmployeeRequestStatus;
use App\Models\EmployeeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeRequestService
{
    public function __construct(
        private readonly WorkOSUserService $workOSUserService
    ) {}

    public function submit(array $data): EmployeeRequest
    {
        return EmployeeRequest::create(array_merge($data, [
            'status' => EmployeeRequestStatus::PENDING,
        ]));
    }

    public function approve(EmployeeRequest $request, User $approvedBy): void
    {
        DB::transaction(function () use ($request, $approvedBy) {
            // Map fields for employee creation
            $employeeData = [
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'chinese_name' => $request->chinese_name,
                'nric' => $request->nric,
                'phone' => $request->phone,
                'mobile' => $request->mobile,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country_id' => $request->country_id,
                'date_of_birth' => $request->date_of_birth?->toDateString(),
                'gender' => $request->gender,
                'race' => $request->race,
                'nationality' => $request->nationality,
                'nationality_id' => $request->nationality_id,
                'residency_status' => $request->residency_status,
                'emergency_name' => $request->emergency_name,
                'emergency_relationship' => $request->emergency_relationship,
                'emergency_contact' => $request->emergency_contact,
                'emergency_address_1' => $request->emergency_address_1,
                'emergency_address_2' => $request->emergency_address_2,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'notes' => $request->notes,
                'role' => 'staff',
            ];

            // Create employee and send WorkOS invitation
            $this->workOSUserService->createEmployee($employeeData);
            $this->workOSUserService->sendInvitation($request->email, 'staff');

            // Update request status
            $request->update([
                'status' => EmployeeRequestStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => $approvedBy->id,
            ]);
        });
    }

    public function reject(EmployeeRequest $request, User $rejectedBy, string $reason): void
    {
        $request->update([
            'status' => EmployeeRequestStatus::REJECTED,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => $rejectedBy->id,
        ]);
    }
}

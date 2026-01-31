<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'company_id' => $this->company_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'salary_amount' => $this->salary_amount,
            'annual_leave_entitled' => $this->annual_leave_entitled,
            'annual_leave_taken' => $this->annual_leave_taken,
            'annual_leave_remaining' => $this->annual_leave_remaining,
            'sick_leave_entitled' => $this->sick_leave_entitled,
            'sick_leave_taken' => $this->sick_leave_taken,
            'sick_leave_remaining' => $this->sick_leave_remaining,
            'external_document_url' => $this->external_document_url,
            'document_url' => $this->document_path
                ? route('users.contracts.document', ['employee' => $this->employee_id, 'contract' => $this->id])
                : null,
            'document_filename' => $this->document_filename,
            'document_mime_type' => $this->document_mime_type,
            'has_document' => $this->hasDocument(),
            'is_document_viewable_inline' => $this->isDocumentViewableInline(),
            'comments' => $this->comments,
            'is_active' => $this->isActive(),
            'company' => $this->company ? (new CompanyResource($this->company))->resolve() : null,
            'employee' => $this->whenLoaded('employee', fn () => $this->employee
                ? (new EmployeeResource($this->employee))->resolve()
                : null),
            'employee_is_deleted' => $this->whenLoaded('employee', fn () => $this->employee?->trashed() ?? false),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'updated_by' => $this->whenLoaded('updatedBy', fn () => [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
            ]),
            'previous_updated_by' => $this->whenLoaded('previousUpdatedBy', fn () => [
                'id' => $this->previousUpdatedBy->id,
                'name' => $this->previousUpdatedBy->name,
            ]),
            'previous_updated_at' => $this->previous_updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

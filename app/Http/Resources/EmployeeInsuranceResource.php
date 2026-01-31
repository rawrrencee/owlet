<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeInsuranceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'title' => $this->title,
            'insurer_name' => $this->insurer_name,
            'policy_number' => $this->policy_number,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'external_document_url' => $this->external_document_url,
            'document_url' => $this->document_path
                ? route('users.insurances.document', ['employee' => $this->employee_id, 'insurance' => $this->id])
                : null,
            'document_filename' => $this->document_filename,
            'document_mime_type' => $this->document_mime_type,
            'has_document' => $this->hasDocument(),
            'is_document_viewable_inline' => $this->isDocumentViewableInline(),
            'comments' => $this->comments,
            'is_active' => $this->isActive(),
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

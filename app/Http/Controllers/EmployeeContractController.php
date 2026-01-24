<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeContractRequest;
use App\Http\Requests\UpdateEmployeeContractRequest;
use App\Http\Resources\EmployeeContractResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeContractController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request, Employee $employee): JsonResponse
    {
        $contracts = $employee->contracts()
            ->with(['company'])
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'data' => EmployeeContractResource::collection($contracts),
        ]);
    }

    public function store(StoreEmployeeContractRequest $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['employee_id'] = $employee->id;
        $data['annual_leave_taken'] = $data['annual_leave_taken'] ?? 0;
        $data['sick_leave_taken'] = $data['sick_leave_taken'] ?? 0;

        // Remove document from data array (handled separately)
        unset($data['document']);

        $contract = EmployeeContract::create($data);

        // Handle document upload if provided
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store(
                "employee-contracts/{$employee->id}",
                'private'
            );

            $contract->update([
                'document_path' => $path,
                'document_filename' => $file->getClientOriginalName(),
                'document_mime_type' => $file->getMimeType(),
            ]);
        }

        $contract->load(['company']);

        return $this->respondWithCreated(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Contract added successfully.',
            new EmployeeContractResource($contract)
        );
    }

    public function update(UpdateEmployeeContractRequest $request, Employee $employee, EmployeeContract $contract): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['annual_leave_taken'] = $data['annual_leave_taken'] ?? 0;
        $data['sick_leave_taken'] = $data['sick_leave_taken'] ?? 0;

        $contract->update($data);
        $contract->load(['company']);

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Contract updated successfully.',
            (new EmployeeContractResource($contract->fresh(['company'])))->resolve()
        );
    }

    public function destroy(Request $request, Employee $employee, EmployeeContract $contract): RedirectResponse|JsonResponse
    {
        // Delete associated document if exists
        if ($contract->document_path) {
            Storage::disk('private')->delete($contract->document_path);
        }

        $contract->delete();

        return $this->respondWithDeleted(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Contract removed successfully.'
        );
    }

    public function showDocument(Employee $employee, EmployeeContract $contract): StreamedResponse
    {
        if (! $contract->document_path || ! Storage::disk('private')->exists($contract->document_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($contract->document_path);
    }

    public function uploadDocument(Request $request, Employee $employee, EmployeeContract $contract): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'document' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,gif,doc,docx'],
        ]);

        // Delete old document if exists
        if ($contract->document_path) {
            Storage::disk('private')->delete($contract->document_path);
        }

        $file = $request->file('document');
        $path = $file->store(
            "employee-contracts/{$employee->id}",
            'private'
        );

        $contract->update([
            'document_path' => $path,
            'document_filename' => $file->getClientOriginalName(),
            'document_mime_type' => $file->getMimeType(),
        ]);

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Document uploaded successfully.',
            [
                'document_url' => route('users.contracts.document', ['employee' => $employee->id, 'contract' => $contract->id]),
                'document_filename' => $contract->document_filename,
                'document_mime_type' => $contract->document_mime_type,
                'is_document_viewable_inline' => $contract->isDocumentViewableInline(),
            ]
        );
    }

    public function deleteDocument(Request $request, Employee $employee, EmployeeContract $contract): RedirectResponse|JsonResponse
    {
        if ($contract->document_path) {
            Storage::disk('private')->delete($contract->document_path);
            $contract->update([
                'document_path' => null,
                'document_filename' => null,
                'document_mime_type' => null,
            ]);
        }

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Document removed successfully.',
            ['document_url' => null]
        );
    }
}

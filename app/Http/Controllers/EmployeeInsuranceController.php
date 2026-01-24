<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeInsuranceRequest;
use App\Http\Requests\UpdateEmployeeInsuranceRequest;
use App\Http\Resources\EmployeeInsuranceResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Employee;
use App\Models\EmployeeInsurance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeInsuranceController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request, Employee $employee): JsonResponse
    {
        $insurances = $employee->insurances()
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'data' => EmployeeInsuranceResource::collection($insurances),
        ]);
    }

    public function store(StoreEmployeeInsuranceRequest $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['employee_id'] = $employee->id;

        // Remove document from data array (handled separately)
        unset($data['document']);

        $insurance = EmployeeInsurance::create($data);

        // Handle document upload if provided
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store(
                "employee-insurances/{$employee->id}",
                'private'
            );

            $insurance->update([
                'document_path' => $path,
                'document_filename' => $file->getClientOriginalName(),
                'document_mime_type' => $file->getMimeType(),
            ]);
        }

        return $this->respondWithCreated(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Insurance added successfully.',
            new EmployeeInsuranceResource($insurance)
        );
    }

    public function update(UpdateEmployeeInsuranceRequest $request, Employee $employee, EmployeeInsurance $insurance): RedirectResponse|JsonResponse
    {
        $insurance->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Insurance updated successfully.',
            (new EmployeeInsuranceResource($insurance->fresh()))->resolve()
        );
    }

    public function destroy(Request $request, Employee $employee, EmployeeInsurance $insurance): RedirectResponse|JsonResponse
    {
        // Delete associated document if exists
        if ($insurance->document_path) {
            Storage::disk('private')->delete($insurance->document_path);
        }

        $insurance->delete();

        return $this->respondWithDeleted(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Insurance removed successfully.'
        );
    }

    public function showDocument(Employee $employee, EmployeeInsurance $insurance): StreamedResponse
    {
        if (! $insurance->document_path || ! Storage::disk('private')->exists($insurance->document_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($insurance->document_path);
    }

    public function uploadDocument(Request $request, Employee $employee, EmployeeInsurance $insurance): RedirectResponse|JsonResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,gif,doc,docx'],
        ]);

        // Delete old document if exists
        if ($insurance->document_path) {
            Storage::disk('private')->delete($insurance->document_path);
        }

        $file = $request->file('document');
        $path = $file->store(
            "employee-insurances/{$employee->id}",
            'private'
        );

        $insurance->update([
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
                'document_url' => route('users.insurances.document', ['employee' => $employee->id, 'insurance' => $insurance->id]),
                'document_filename' => $insurance->document_filename,
                'document_mime_type' => $insurance->document_mime_type,
                'is_document_viewable_inline' => $insurance->isDocumentViewableInline(),
            ]
        );
    }

    public function deleteDocument(Request $request, Employee $employee, EmployeeInsurance $insurance): RedirectResponse|JsonResponse
    {
        if ($insurance->document_path) {
            Storage::disk('private')->delete($insurance->document_path);
            $insurance->update([
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

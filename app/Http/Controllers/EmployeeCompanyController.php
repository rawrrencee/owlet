<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeCompanyRequest;
use App\Http\Requests\UpdateEmployeeCompanyRequest;
use App\Http\Resources\EmployeeCompanyResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Employee;
use App\Models\EmployeeCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeCompanyController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request, Employee $employee): JsonResponse
    {
        $employeeCompanies = $employee->employeeCompanies()
            ->with(['company', 'designation'])
            ->orderBy('commencement_date', 'desc')
            ->get();

        return response()->json([
            'data' => EmployeeCompanyResource::collection($employeeCompanies),
        ]);
    }

    public function store(StoreEmployeeCompanyRequest $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['employee_id'] = $employee->id;

        $employeeCompany = EmployeeCompany::create($data);
        $employeeCompany->load(['company', 'designation']);

        return $this->respondWithCreated(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Company assignment added successfully.',
            new EmployeeCompanyResource($employeeCompany)
        );
    }

    public function show(Request $request, EmployeeCompany $employeeCompany): JsonResponse
    {
        $employeeCompany->load(['company', 'designation', 'employee']);

        return response()->json([
            'data' => (new EmployeeCompanyResource($employeeCompany))->resolve(),
        ]);
    }

    public function update(UpdateEmployeeCompanyRequest $request, Employee $employee, EmployeeCompany $employeeCompany): RedirectResponse|JsonResponse
    {
        $employeeCompany->update($request->validated());
        $employeeCompany->load(['company', 'designation']);

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Company assignment updated successfully.',
            (new EmployeeCompanyResource($employeeCompany->fresh(['company', 'designation'])))->resolve()
        );
    }

    public function destroy(Request $request, Employee $employee, EmployeeCompany $employeeCompany): RedirectResponse|JsonResponse
    {
        $employeeCompany->delete();

        return $this->respondWithDeleted(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Company assignment removed successfully.'
        );
    }
}

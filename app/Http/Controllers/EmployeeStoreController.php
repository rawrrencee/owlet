<?php

namespace App\Http\Controllers;

use App\Constants\StorePermissions;
use App\Http\Requests\StoreEmployeeStoreRequest;
use App\Http\Requests\UpdateEmployeeStoreRequest;
use App\Http\Resources\EmployeeStoreResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Employee;
use App\Models\EmployeeStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeStoreController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request, Employee $employee): JsonResponse
    {
        $employeeStores = $employee->employeeStores()
            ->with('store.company')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => EmployeeStoreResource::collection($employeeStores),
            'available_permissions' => StorePermissions::grouped(),
        ]);
    }

    public function store(StoreEmployeeStoreRequest $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['employee_id'] = $employee->id;

        $employeeStore = EmployeeStore::create($data);
        $employeeStore->load('store.company');

        return $this->respondWithCreated(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Store assignment added successfully.',
            new EmployeeStoreResource($employeeStore)
        );
    }

    public function show(Request $request, EmployeeStore $employeeStore): JsonResponse
    {
        $employeeStore->load(['store.company', 'employee']);

        return response()->json([
            'data' => (new EmployeeStoreResource($employeeStore))->resolve(),
            'available_permissions' => StorePermissions::grouped(),
        ]);
    }

    public function update(UpdateEmployeeStoreRequest $request, Employee $employee, EmployeeStore $employeeStore): RedirectResponse|JsonResponse
    {
        $employeeStore->update($request->validated());
        $employeeStore->load('store.company');

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Store assignment updated successfully.',
            (new EmployeeStoreResource($employeeStore->fresh('store.company')))->resolve()
        );
    }

    public function destroy(Request $request, Employee $employee, EmployeeStore $employeeStore): RedirectResponse|JsonResponse
    {
        $employeeStore->delete();

        return $this->respondWithDeleted(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Store assignment removed successfully.'
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LeaveTypeController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $leaveTypes = LeaveType::orderBy('sort_order')->orderBy('name')->get();

        return $this->respondWith(
            $request,
            'Management/LeaveTypes/Index',
            [
                'leaveTypes' => LeaveTypeResource::collection($leaveTypes)->resolve(),
                'breadcrumbs' => [
                    ['title' => 'System'],
                    ['title' => 'Leave Types'],
                ],
            ],
            LeaveTypeResource::collection($leaveTypes)
        );
    }

    public function create(Request $request): InertiaResponse
    {
        return Inertia::render('Management/LeaveTypes/Form', [
            'breadcrumbs' => [
                ['title' => 'System'],
                ['title' => 'Leave Types', 'href' => '/management/leave-types'],
                ['title' => 'Create'],
            ],
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse|JsonResponse
    {
        $leaveType = LeaveType::create($request->validated());

        return $this->respondWithCreated(
            $request,
            'management.leave-types.index',
            [],
            'Leave type created successfully.',
            new LeaveTypeResource($leaveType)
        );
    }

    public function edit(Request $request, LeaveType $leaveType): InertiaResponse
    {
        return Inertia::render('Management/LeaveTypes/Form', [
            'leaveType' => (new LeaveTypeResource($leaveType))->resolve(),
            'breadcrumbs' => [
                ['title' => 'System'],
                ['title' => 'Leave Types', 'href' => '/management/leave-types'],
                ['title' => 'Edit'],
            ],
        ]);
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse|JsonResponse
    {
        $leaveType->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'management.leave-types.index',
            [],
            'Leave type updated successfully.',
            (new LeaveTypeResource($leaveType->fresh()))->resolve()
        );
    }

    public function destroy(Request $request, LeaveType $leaveType): RedirectResponse|JsonResponse
    {
        // Check if leave type has any requests
        if ($leaveType->leaveRequests()->exists()) {
            return $this->respondWithError(
                $request,
                'Cannot delete leave type that has associated leave requests.'
            );
        }

        $leaveType->delete();

        return $this->respondWithDeleted(
            $request,
            'management.leave-types.index',
            [],
            'Leave type deleted successfully.'
        );
    }
}

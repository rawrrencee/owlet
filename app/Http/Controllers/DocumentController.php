<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentContractRequest;
use App\Http\Requests\StoreDocumentInsuranceRequest;
use App\Http\Requests\UpdateEmployeeContractRequest;
use App\Http\Requests\UpdateEmployeeInsuranceRequest;
use App\Http\Resources\EmployeeContractResource;
use App\Http\Resources\EmployeeInsuranceResource;
use App\Http\Resources\LeaveTypeResource;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeInsurance;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->get('type', 'contracts');
        $filters = $request->only(['search', 'status', 'company']);
        $showDeleted = $request->boolean('show_deleted');
        $perPage = min(max($request->integer('per_page', 15), 10), 100);
        $filters['show_deleted'] = $showDeleted;
        $filters['per_page'] = $perPage;

        if ($type === 'insurances') {
            $data = $this->getInsurances($filters);

            return Inertia::render('Documents/Index', [
                'documents' => $data,
                'type' => 'insurances',
                'filters' => $filters,
            ]);
        }

        $data = $this->getContracts($filters);

        return Inertia::render('Documents/Index', [
            'documents' => $data,
            'type' => 'contracts',
            'filters' => $filters,
            'companies' => Company::query()->where('active', true)->orderBy('company_name')->get(),
        ]);
    }

    private function getContracts(array $filters): array
    {
        $showDeleted = ! empty($filters['show_deleted']);
        $perPage = $filters['per_page'] ?? 15;

        $query = EmployeeContract::query()
            ->with(['employee' => fn ($q) => $showDeleted ? $q->withTrashed() : $q, 'company'])
            ->orderBy('start_date', 'desc');

        // Filter out contracts for deleted employees unless show_deleted is enabled
        if ($showDeleted) {
            $query->whereHas('employee', fn ($q) => $q->withTrashed());
        } else {
            $query->whereHas('employee');
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $searchQuery = $showDeleted
                ? fn ($eq) => $eq->withTrashed()->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                : fn ($eq) => $eq->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%");

            $query->where(function ($q) use ($search, $searchQuery) {
                $q->whereHas('employee', $searchQuery)
                    ->orWhereHas('company', function ($cq) use ($search) {
                        $cq->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $today = now()->startOfDay();
            if ($filters['status'] === 'active') {
                $query->where(function ($q) use ($today) {
                    $q->where('start_date', '<=', $today)
                        ->where(function ($inner) use ($today) {
                            $inner->whereNull('end_date')
                                ->orWhere('end_date', '>=', $today);
                        });
                });
            } elseif ($filters['status'] === 'expired') {
                $query->where('end_date', '<', $today);
            }
        }

        if (! empty($filters['company'])) {
            $query->where('company_id', $filters['company']);
        }

        $contracts = $query->paginate($perPage)->withQueryString();

        return [
            'data' => EmployeeContractResource::collection($contracts->items())->resolve(),
            'current_page' => $contracts->currentPage(),
            'last_page' => $contracts->lastPage(),
            'per_page' => $contracts->perPage(),
            'total' => $contracts->total(),
        ];
    }

    private function getInsurances(array $filters): array
    {
        $showDeleted = ! empty($filters['show_deleted']);
        $perPage = $filters['per_page'] ?? 15;

        $query = EmployeeInsurance::query()
            ->with(['employee' => fn ($q) => $showDeleted ? $q->withTrashed() : $q])
            ->orderBy('start_date', 'desc');

        // Filter out insurances for deleted employees unless show_deleted is enabled
        if ($showDeleted) {
            $query->whereHas('employee', fn ($q) => $q->withTrashed());
        } else {
            $query->whereHas('employee');
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $searchQuery = $showDeleted
                ? fn ($eq) => $eq->withTrashed()->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                : fn ($eq) => $eq->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%");

            $query->where(function ($q) use ($search, $searchQuery) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('insurer_name', 'like', "%{$search}%")
                    ->orWhere('policy_number', 'like', "%{$search}%")
                    ->orWhereHas('employee', $searchQuery);
            });
        }

        if (! empty($filters['status'])) {
            $today = now()->startOfDay();
            if ($filters['status'] === 'active') {
                $query->where(function ($q) use ($today) {
                    $q->where('start_date', '<=', $today)
                        ->where(function ($inner) use ($today) {
                            $inner->whereNull('end_date')
                                ->orWhere('end_date', '>=', $today);
                        });
                });
            } elseif ($filters['status'] === 'expired') {
                $query->where('end_date', '<', $today);
            }
        }

        $insurances = $query->paginate($perPage)->withQueryString();

        return [
            'data' => EmployeeInsuranceResource::collection($insurances->items())->resolve(),
            'current_page' => $insurances->currentPage(),
            'last_page' => $insurances->lastPage(),
            'per_page' => $insurances->perPage(),
            'total' => $insurances->total(),
        ];
    }

    public function createContract(Request $request): Response
    {
        $employees = Employee::query()
            ->whereNull('termination_date')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        $companies = Company::query()
            ->where('active', true)
            ->orderBy('company_name')
            ->get();

        $data = [
            'employees' => $employees,
            'companies' => $companies,
            'leaveTypes' => LeaveTypeResource::collection(LeaveType::active()->orderBy('sort_order')->get()),
        ];

        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::query()
                ->whereNull('termination_date')
                ->find($request->get('employee_id'), ['id', 'first_name', 'last_name']);

            if ($selectedEmployee) {
                $data['selectedEmployee'] = $selectedEmployee;
                $data['employeeContracts'] = EmployeeContractResource::collection(
                    $selectedEmployee->contracts()->with(['company', 'leaveEntitlements.leaveType'])->orderBy('start_date', 'desc')->get()
                )->resolve();
            }
        }

        return Inertia::render('Documents/Contracts/Create', $data);
    }

    public function storeContract(StoreDocumentContractRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employeeId = $data['employee_id'];
        $entitlements = $data['entitlements'] ?? [];

        // Remove non-contract fields
        unset($data['document'], $data['entitlements']);

        $contract = EmployeeContract::create($data);

        // Create leave entitlements
        foreach ($entitlements as $entitlement) {
            $contract->leaveEntitlements()->create([
                'leave_type_id' => $entitlement['leave_type_id'],
                'entitled_days' => $entitlement['entitled_days'],
                'taken_days' => $entitlement['taken_days'] ?? 0,
            ]);
        }

        // Handle document upload if provided
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store(
                "employee-contracts/{$employeeId}",
                'private'
            );

            $contract->update([
                'document_path' => $path,
                'document_filename' => $file->getClientOriginalName(),
                'document_mime_type' => $file->getMimeType(),
            ]);
        }

        return redirect()
            ->route('documents.contracts.show', $contract)
            ->with('success', 'Contract created successfully.');
    }

    public function createInsurance(Request $request): Response
    {
        $employees = Employee::query()
            ->whereNull('termination_date')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        $data = [
            'employees' => $employees,
        ];

        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::query()
                ->whereNull('termination_date')
                ->find($request->get('employee_id'), ['id', 'first_name', 'last_name']);

            if ($selectedEmployee) {
                $data['selectedEmployee'] = $selectedEmployee;
                $data['employeeInsurances'] = EmployeeInsuranceResource::collection(
                    $selectedEmployee->insurances()->orderBy('start_date', 'desc')->get()
                )->resolve();
            }
        }

        return Inertia::render('Documents/Insurances/Create', $data);
    }

    public function storeInsurance(StoreDocumentInsuranceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employeeId = $data['employee_id'];

        // Remove document from data array (handled separately)
        unset($data['document']);

        $insurance = EmployeeInsurance::create($data);

        // Handle document upload if provided
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store(
                "employee-insurances/{$employeeId}",
                'private'
            );

            $insurance->update([
                'document_path' => $path,
                'document_filename' => $file->getClientOriginalName(),
                'document_mime_type' => $file->getMimeType(),
            ]);
        }

        return redirect()
            ->route('documents.insurances.show', $insurance)
            ->with('success', 'Insurance created successfully.');
    }

    public function showContract(EmployeeContract $contract): Response
    {
        $contract->load([
            'employee' => fn ($q) => $q->withTrashed(),
            'company',
            'leaveEntitlements.leaveType',
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        return Inertia::render('Documents/Contracts/View', [
            'contract' => (new EmployeeContractResource($contract))->resolve(),
        ]);
    }

    public function editContract(EmployeeContract $contract): Response
    {
        $contract->load(['employee' => fn ($q) => $q->withTrashed(), 'company', 'leaveEntitlements.leaveType']);

        return Inertia::render('Documents/Contracts/Edit', [
            'contract' => (new EmployeeContractResource($contract))->resolve(),
            'companies' => Company::query()->where('active', true)->orderBy('company_name')->get(),
            'leaveTypes' => LeaveTypeResource::collection(LeaveType::active()->orderBy('sort_order')->get()),
        ]);
    }

    public function updateContract(UpdateEmployeeContractRequest $request, EmployeeContract $contract): RedirectResponse
    {
        $data = $request->validated();
        $entitlements = $data['entitlements'] ?? [];
        unset($data['entitlements']);

        $contract->update($data);

        // Sync leave entitlements
        $existingIds = [];
        foreach ($entitlements as $entitlement) {
            $record = $contract->leaveEntitlements()->updateOrCreate(
                ['leave_type_id' => $entitlement['leave_type_id']],
                [
                    'entitled_days' => $entitlement['entitled_days'],
                    'taken_days' => $entitlement['taken_days'] ?? 0,
                ]
            );
            $existingIds[] = $record->id;
        }
        // Remove entitlements that are no longer present
        $contract->leaveEntitlements()->whereNotIn('id', $existingIds)->delete();

        return redirect()
            ->route('documents.contracts.show', $contract)
            ->with('success', 'Contract updated successfully.');
    }

    public function showContractDocument(EmployeeContract $contract): StreamedResponse
    {
        if (! $contract->document_path || ! Storage::disk('private')->exists($contract->document_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($contract->document_path);
    }

    public function uploadContractDocument(Request $request, EmployeeContract $contract): RedirectResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,gif,doc,docx'],
        ]);

        // Delete old document if exists
        if ($contract->document_path) {
            Storage::disk('private')->delete($contract->document_path);
        }

        $file = $request->file('document');
        $path = $file->store(
            "employee-contracts/{$contract->employee_id}",
            'private'
        );

        $contract->update([
            'document_path' => $path,
            'document_filename' => $file->getClientOriginalName(),
            'document_mime_type' => $file->getMimeType(),
        ]);

        return redirect()
            ->route('documents.contracts.edit', $contract)
            ->with('success', 'Document uploaded successfully.');
    }

    public function deleteContractDocument(EmployeeContract $contract): RedirectResponse
    {
        if ($contract->document_path) {
            Storage::disk('private')->delete($contract->document_path);
            $contract->update([
                'document_path' => null,
                'document_filename' => null,
                'document_mime_type' => null,
            ]);
        }

        return redirect()
            ->route('documents.contracts.edit', $contract)
            ->with('success', 'Document removed successfully.');
    }

    public function showInsurance(EmployeeInsurance $insurance): Response
    {
        $insurance->load([
            'employee' => fn ($q) => $q->withTrashed(),
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        return Inertia::render('Documents/Insurances/View', [
            'insurance' => (new EmployeeInsuranceResource($insurance))->resolve(),
        ]);
    }

    public function editInsurance(EmployeeInsurance $insurance): Response
    {
        $insurance->load(['employee' => fn ($q) => $q->withTrashed()]);

        return Inertia::render('Documents/Insurances/Edit', [
            'insurance' => (new EmployeeInsuranceResource($insurance))->resolve(),
        ]);
    }

    public function updateInsurance(UpdateEmployeeInsuranceRequest $request, EmployeeInsurance $insurance): RedirectResponse
    {
        $insurance->update($request->validated());

        return redirect()
            ->route('documents.insurances.show', $insurance)
            ->with('success', 'Insurance updated successfully.');
    }

    public function showInsuranceDocument(EmployeeInsurance $insurance): StreamedResponse
    {
        if (! $insurance->document_path || ! Storage::disk('private')->exists($insurance->document_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($insurance->document_path);
    }

    public function uploadInsuranceDocument(Request $request, EmployeeInsurance $insurance): RedirectResponse
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
            "employee-insurances/{$insurance->employee_id}",
            'private'
        );

        $insurance->update([
            'document_path' => $path,
            'document_filename' => $file->getClientOriginalName(),
            'document_mime_type' => $file->getMimeType(),
        ]);

        return redirect()
            ->route('documents.insurances.edit', $insurance)
            ->with('success', 'Document uploaded successfully.');
    }

    public function deleteInsuranceDocument(EmployeeInsurance $insurance): RedirectResponse
    {
        if ($insurance->document_path) {
            Storage::disk('private')->delete($insurance->document_path);
            $insurance->update([
                'document_path' => null,
                'document_filename' => null,
                'document_mime_type' => null,
            ]);
        }

        return redirect()
            ->route('documents.insurances.edit', $insurance)
            ->with('success', 'Document removed successfully.');
    }
}

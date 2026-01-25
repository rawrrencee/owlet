<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $showDeleted = $request->boolean('show_deleted', false);

        $query = Company::query();

        if ($showDeleted) {
            $query->withTrashed();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        $companies = $query
            ->orderBy('company_name')
            ->paginate(15)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return CompanyResource::collection($companies)->response();
        }

        $transformedCompanies = [
            'data' => CompanyResource::collection($companies->items())->resolve(),
            'current_page' => $companies->currentPage(),
            'last_page' => $companies->lastPage(),
            'per_page' => $companies->perPage(),
            'total' => $companies->total(),
        ];

        return Inertia::render('Companies/Index', [
            'companies' => $transformedCompanies,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'show_deleted' => $showDeleted,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Companies/Form', [
            'company' => null,
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse|JsonResponse
    {
        $data = collect($request->validated())->except('logo')->toArray();
        $company = Company::create($data);

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store(
                "company-logos/{$company->id}",
                'private'
            );
            $company->update(['logo' => $path]);
        }

        return $this->respondWithCreated(
            $request,
            'companies.index',
            [],
            'Company created successfully.',
            new CompanyResource($company->fresh())
        );
    }

    public function show(Request $request, Company $company): InertiaResponse|JsonResponse
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new CompanyResource($company))->resolve(),
            ]);
        }

        return Inertia::render('Companies/Form', [
            'company' => (new CompanyResource($company))->resolve(),
        ]);
    }

    public function edit(Company $company): InertiaResponse
    {
        return Inertia::render('Companies/Form', [
            'company' => (new CompanyResource($company))->resolve(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse|JsonResponse
    {
        $company->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'companies.index',
            [],
            'Company updated successfully.',
            (new CompanyResource($company->fresh()))->resolve()
        );
    }

    public function destroy(Request $request, Company $company): RedirectResponse|JsonResponse
    {
        // Delete logo if exists
        if ($company->logo) {
            Storage::disk('private')->delete($company->logo);
        }

        $company->delete();

        return $this->respondWithDeleted(
            $request,
            'companies.index',
            [],
            'Company deleted successfully.'
        );
    }

    public function restore(Request $request, Company $company): RedirectResponse|JsonResponse
    {
        $company->restore();

        return $this->respondWithSuccess(
            $request,
            'companies.index',
            ['show_deleted' => true],
            'Company restored successfully.',
            (new CompanyResource($company->fresh()))->resolve()
        );
    }

    public function showLogo(Company $company): StreamedResponse
    {
        if (! $company->logo || ! Storage::disk('private')->exists($company->logo)) {
            abort(404);
        }

        return Storage::disk('private')->response($company->logo);
    }

    public function uploadLogo(Company $company, Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Delete old logo if exists
        if ($company->logo) {
            Storage::disk('private')->delete($company->logo);
        }

        // Store new logo
        $path = $request->file('logo')->store(
            "company-logos/{$company->id}",
            'private'
        );

        $company->update(['logo' => $path]);

        return $this->respondWithSuccess(
            $request,
            'companies.edit',
            ['company' => $company->id],
            'Logo uploaded successfully.',
            ['logo_url' => route('companies.logo', $company->id)]
        );
    }

    public function deleteLogo(Company $company, Request $request): RedirectResponse|JsonResponse
    {
        if ($company->logo) {
            Storage::disk('private')->delete($company->logo);
            $company->update(['logo' => null]);
        }

        return $this->respondWithSuccess(
            $request,
            'companies.edit',
            ['company' => $company->id],
            'Logo removed successfully.',
            ['logo_url' => null]
        );
    }
}

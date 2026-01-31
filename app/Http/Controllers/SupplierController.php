<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Country;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $countryId = $request->query('country_id', '');
        $showDeleted = $request->boolean('show_deleted', false);

        $query = Supplier::with(['country']);

        if ($showDeleted) {
            $query->withTrashed();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        $suppliers = $query
            ->orderBy('supplier_name')
            ->paginate(15)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return SupplierResource::collection($suppliers)->response();
        }

        $transformedSuppliers = [
            'data' => SupplierResource::collection($suppliers->items())->resolve(),
            'current_page' => $suppliers->currentPage(),
            'last_page' => $suppliers->lastPage(),
            'per_page' => $suppliers->perPage(),
            'total' => $suppliers->total(),
        ];

        $countries = Country::active()->ordered()->get(['id', 'name', 'code']);

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $transformedSuppliers,
            'countries' => $countries,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'country_id' => $countryId,
                'show_deleted' => $showDeleted,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Suppliers/Form', [
            'supplier' => null,
            'countries' => Country::active()->ordered()->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse|JsonResponse
    {
        $data = collect($request->validated())->except('logo')->toArray();
        $supplier = Supplier::create($data);

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store(
                "supplier-logos/{$supplier->id}",
                'private'
            );
            $supplier->update([
                'logo_path' => $path,
                'logo_filename' => $file->getClientOriginalName(),
                'logo_mime_type' => $file->getMimeType(),
            ]);
        }

        return $this->respondWithCreated(
            $request,
            'suppliers.index',
            [],
            'Supplier created successfully.',
            new SupplierResource($supplier->fresh('country'))
        );
    }

    public function show(Request $request, Supplier $supplier): InertiaResponse|JsonResponse
    {
        $supplier->load([
            'country',
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new SupplierResource($supplier))->resolve(),
            ]);
        }

        return Inertia::render('Suppliers/View', [
            'supplier' => (new SupplierResource($supplier))->resolve(),
        ]);
    }

    public function edit(Supplier $supplier): InertiaResponse
    {
        $supplier->load(['country']);

        return Inertia::render('Suppliers/Form', [
            'supplier' => (new SupplierResource($supplier))->resolve(),
            'countries' => Country::active()->ordered()->get(['id', 'name', 'code']),
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $supplier->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'suppliers.show',
            ['supplier' => $supplier->id],
            'Supplier updated successfully.',
            (new SupplierResource($supplier->fresh('country')))->resolve()
        );
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $supplier->delete();

        return $this->respondWithDeleted(
            $request,
            'suppliers.index',
            [],
            'Supplier deleted successfully.'
        );
    }

    public function restore(Request $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $supplier->restore();

        return $this->respondWithSuccess(
            $request,
            'suppliers.index',
            ['show_deleted' => true],
            'Supplier restored successfully.',
            (new SupplierResource($supplier->fresh('country')))->resolve()
        );
    }

    public function showLogo(Supplier $supplier): StreamedResponse
    {
        if (! $supplier->logo_path || ! Storage::disk('private')->exists($supplier->logo_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($supplier->logo_path);
    }

    public function uploadLogo(Supplier $supplier, Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Delete old logo if exists
        if ($supplier->logo_path) {
            Storage::disk('private')->delete($supplier->logo_path);
        }

        // Store new logo
        $file = $request->file('logo');
        $path = $file->store(
            "supplier-logos/{$supplier->id}",
            'private'
        );

        $supplier->update([
            'logo_path' => $path,
            'logo_filename' => $file->getClientOriginalName(),
            'logo_mime_type' => $file->getMimeType(),
        ]);

        return $this->respondWithSuccess(
            $request,
            'suppliers.edit',
            ['supplier' => $supplier->id],
            'Logo uploaded successfully.',
            ['logo_url' => route('suppliers.logo', $supplier->id)]
        );
    }

    public function deleteLogo(Supplier $supplier, Request $request): RedirectResponse|JsonResponse
    {
        if ($supplier->logo_path) {
            Storage::disk('private')->delete($supplier->logo_path);
            $supplier->update([
                'logo_path' => null,
                'logo_filename' => null,
                'logo_mime_type' => null,
            ]);
        }

        return $this->respondWithSuccess(
            $request,
            'suppliers.edit',
            ['supplier' => $supplier->id],
            'Logo removed successfully.',
            ['logo_url' => null]
        );
    }
}

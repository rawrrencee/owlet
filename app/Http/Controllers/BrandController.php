<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Brand;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $countryId = $request->query('country_id', '');
        $showDeleted = $request->boolean('show_deleted', false);
        $perPage = min(max($request->integer('per_page', 15), 10), 100);

        $query = Brand::with(['country']);

        if ($showDeleted) {
            $query->withTrashed();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('brand_name', 'like', "%{$search}%")
                    ->orWhere('brand_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        $brands = $query
            ->orderBy('brand_name')
            ->paginate($perPage)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return BrandResource::collection($brands)->response();
        }

        $transformedBrands = [
            'data' => BrandResource::collection($brands->items())->resolve(),
            'current_page' => $brands->currentPage(),
            'last_page' => $brands->lastPage(),
            'per_page' => $brands->perPage(),
            'total' => $brands->total(),
        ];

        $countries = Country::active()->ordered()->get(['id', 'name', 'code']);

        return Inertia::render('Brands/Index', [
            'brands' => $transformedBrands,
            'countries' => $countries,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'country_id' => $countryId,
                'show_deleted' => $showDeleted,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Brands/Form', [
            'brand' => null,
            'countries' => Country::active()->ordered()->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse|JsonResponse
    {
        $data = collect($request->validated())->except('logo')->toArray();
        $brand = Brand::create($data);

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store(
                "brand-logos/{$brand->id}",
                'private'
            );
            $brand->update([
                'logo_path' => $path,
                'logo_filename' => $file->getClientOriginalName(),
                'logo_mime_type' => $file->getMimeType(),
            ]);
        }

        return $this->respondWithCreated(
            $request,
            'brands.index',
            [],
            'Brand created successfully.',
            new BrandResource($brand->fresh('country'))
        );
    }

    public function show(Request $request, Brand $brand): InertiaResponse|JsonResponse
    {
        $brand->load([
            'country',
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new BrandResource($brand))->resolve(),
            ]);
        }

        return Inertia::render('Brands/View', [
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }

    public function edit(Brand $brand): InertiaResponse
    {
        $brand->load(['country']);

        return Inertia::render('Brands/Form', [
            'brand' => (new BrandResource($brand))->resolve(),
            'countries' => Country::active()->ordered()->get(['id', 'name', 'code']),
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse|JsonResponse
    {
        $brand->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'brands.show',
            ['brand' => $brand->id],
            'Brand updated successfully.',
            (new BrandResource($brand->fresh('country')))->resolve()
        );
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse|JsonResponse
    {
        // Delete logo if exists
        if ($brand->logo_path) {
            Storage::disk('private')->delete($brand->logo_path);
        }

        $brand->delete();

        return $this->respondWithDeleted(
            $request,
            'brands.index',
            [],
            'Brand deleted successfully.'
        );
    }

    public function restore(Request $request, Brand $brand): RedirectResponse|JsonResponse
    {
        $brand->restore();

        return $this->respondWithSuccess(
            $request,
            'brands.index',
            ['show_deleted' => true],
            'Brand restored successfully.',
            (new BrandResource($brand->fresh('country')))->resolve()
        );
    }

    public function showLogo(Brand $brand): StreamedResponse
    {
        if (! $brand->logo_path || ! Storage::disk('private')->exists($brand->logo_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($brand->logo_path);
    }

    public function uploadLogo(Brand $brand, Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Delete old logo if exists
        if ($brand->logo_path) {
            Storage::disk('private')->delete($brand->logo_path);
        }

        // Store new logo
        $file = $request->file('logo');
        $path = $file->store(
            "brand-logos/{$brand->id}",
            'private'
        );

        $brand->update([
            'logo_path' => $path,
            'logo_filename' => $file->getClientOriginalName(),
            'logo_mime_type' => $file->getMimeType(),
        ]);

        return $this->respondWithSuccess(
            $request,
            'brands.edit',
            ['brand' => $brand->id],
            'Logo uploaded successfully.',
            ['logo_url' => route('brands.logo', $brand->id)]
        );
    }

    public function deleteLogo(Brand $brand, Request $request): RedirectResponse|JsonResponse
    {
        if ($brand->logo_path) {
            Storage::disk('private')->delete($brand->logo_path);
            $brand->update([
                'logo_path' => null,
                'logo_filename' => null,
                'logo_mime_type' => null,
            ]);
        }

        return $this->respondWithSuccess(
            $request,
            'brands.edit',
            ['brand' => $brand->id],
            'Logo removed successfully.',
            ['logo_url' => null]
        );
    }
}

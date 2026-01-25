<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDesignationRequest;
use App\Http\Requests\UpdateDesignationRequest;
use App\Http\Resources\DesignationResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DesignationController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $search = $request->query('search', '');

        $query = Designation::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('designation_name', 'like', "%{$search}%")
                    ->orWhere('designation_code', 'like', "%{$search}%");
            });
        }

        $designations = $query
            ->orderBy('designation_name')
            ->paginate(15)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return DesignationResource::collection($designations)->response();
        }

        $transformedDesignations = [
            'data' => DesignationResource::collection($designations->items())->resolve(),
            'current_page' => $designations->currentPage(),
            'last_page' => $designations->lastPage(),
            'per_page' => $designations->perPage(),
            'total' => $designations->total(),
        ];

        return Inertia::render('Designations/Index', [
            'designations' => $transformedDesignations,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Designations/Form', [
            'designation' => null,
        ]);
    }

    public function store(StoreDesignationRequest $request): RedirectResponse|JsonResponse
    {
        $designation = Designation::create($request->validated());

        return $this->respondWithCreated(
            $request,
            'designations.index',
            [],
            'Designation created successfully.',
            new DesignationResource($designation)
        );
    }

    public function show(Request $request, Designation $designation): InertiaResponse|JsonResponse
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new DesignationResource($designation))->resolve(),
            ]);
        }

        return Inertia::render('Designations/Form', [
            'designation' => (new DesignationResource($designation))->resolve(),
        ]);
    }

    public function edit(Designation $designation): InertiaResponse
    {
        return Inertia::render('Designations/Form', [
            'designation' => (new DesignationResource($designation))->resolve(),
        ]);
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): RedirectResponse|JsonResponse
    {
        $designation->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'designations.index',
            [],
            'Designation updated successfully.',
            (new DesignationResource($designation->fresh()))->resolve()
        );
    }

    public function destroy(Request $request, Designation $designation): RedirectResponse|JsonResponse
    {
        $designation->delete();

        return $this->respondWithDeleted(
            $request,
            'designations.index',
            [],
            'Designation deleted successfully.'
        );
    }
}

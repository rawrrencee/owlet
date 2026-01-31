<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\StoreSubcategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Requests\UpdateSubcategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SubcategoryResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CategoryController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $showDeleted = $request->boolean('show_deleted', false);
        $searchSubcategories = $request->boolean('search_subcategories', false);

        $query = Category::with(['subcategories' => function ($q) {
            $q->orderBy('is_default', 'desc')->orderBy('subcategory_name');
        }])->withCount(['subcategories', 'activeSubcategories']);

        if ($showDeleted) {
            $query->withTrashed();
        }

        if ($search) {
            $query->where(function ($q) use ($search, $searchSubcategories) {
                $q->where('category_name', 'like', "%{$search}%")
                    ->orWhere('category_code', 'like', "%{$search}%");

                // Also search in subcategories if enabled
                if ($searchSubcategories) {
                    $q->orWhereHas('subcategories', function ($subQuery) use ($search) {
                        $subQuery->where('subcategory_name', 'like', "%{$search}%")
                            ->orWhere('subcategory_code', 'like', "%{$search}%");
                    });
                }
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $categories = $query
            ->orderBy('category_name')
            ->paginate(15)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return CategoryResource::collection($categories)->response();
        }

        $transformedCategories = [
            'data' => CategoryResource::collection($categories->items())->resolve(),
            'current_page' => $categories->currentPage(),
            'last_page' => $categories->lastPage(),
            'per_page' => $categories->perPage(),
            'total' => $categories->total(),
        ];

        return Inertia::render('Categories/Index', [
            'categories' => $transformedCategories,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'show_deleted' => $showDeleted,
                'search_subcategories' => $searchSubcategories,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Categories/Form', [
            'category' => null,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse|JsonResponse
    {
        $category = Category::create($request->validated());

        // Auto-create default subcategory
        $category->subcategories()->create([
            'subcategory_name' => 'Default',
            'subcategory_code' => 'DFLT',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Redirect to edit page so user can add more subcategories
        return $this->respondWithCreated(
            $request,
            'categories.edit',
            ['category' => $category->id],
            'Category created successfully. You can now add subcategories.',
            new CategoryResource($category->load('subcategories'))
        );
    }

    public function show(Request $request, Category $category): InertiaResponse|JsonResponse
    {
        $category->load([
            'subcategories' => function ($query) {
                $query->withTrashed()->orderBy('is_default', 'desc')->orderBy('subcategory_name');
            },
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new CategoryResource($category))->resolve(),
            ]);
        }

        return Inertia::render('Categories/View', [
            'category' => (new CategoryResource($category))->resolve(),
        ]);
    }

    public function edit(Category $category): InertiaResponse
    {
        $category->load(['subcategories' => function ($query) {
            $query->withTrashed()->orderBy('is_default', 'desc')->orderBy('subcategory_name');
        }]);

        return Inertia::render('Categories/Form', [
            'category' => (new CategoryResource($category))->resolve(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        $category->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'categories.show',
            ['category' => $category->id],
            'Category updated successfully.',
            (new CategoryResource($category->fresh()))->resolve()
        );
    }

    public function destroy(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $category->delete();

        return $this->respondWithDeleted(
            $request,
            'categories.index',
            [],
            'Category deleted successfully.'
        );
    }

    public function restore(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $category->restore();

        return $this->respondWithSuccess(
            $request,
            'categories.index',
            ['show_deleted' => true],
            'Category restored successfully.',
            (new CategoryResource($category->fresh()))->resolve()
        );
    }

    // Subcategory management methods

    public function storeSubcategory(StoreSubcategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        $subcategory = $category->subcategories()->create($request->validated());

        return $this->respondWithCreated(
            $request,
            'categories.edit',
            ['category' => $category->id],
            'Subcategory created successfully.',
            new SubcategoryResource($subcategory)
        );
    }

    public function updateSubcategory(
        UpdateSubcategoryRequest $request,
        Category $category,
        Subcategory $subcategory
    ): RedirectResponse|JsonResponse {
        // Ensure subcategory belongs to category
        if ($subcategory->category_id !== $category->id) {
            abort(404);
        }

        $subcategory->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'categories.edit',
            ['category' => $category->id],
            'Subcategory updated successfully.',
            (new SubcategoryResource($subcategory->fresh()))->resolve()
        );
    }

    public function destroySubcategory(
        Request $request,
        Category $category,
        Subcategory $subcategory
    ): RedirectResponse|JsonResponse {
        // Ensure subcategory belongs to category
        if ($subcategory->category_id !== $category->id) {
            abort(404);
        }

        // Cannot delete default subcategory
        if ($subcategory->is_default) {
            return $this->respondWithError(
                $request,
                'Cannot delete the default subcategory.',
                [],
                422
            );
        }

        $subcategory->delete();

        return $this->respondWithDeleted(
            $request,
            'categories.edit',
            ['category' => $category->id],
            'Subcategory deleted successfully.'
        );
    }

    public function restoreSubcategory(
        Request $request,
        Category $category,
        int $subcategory
    ): RedirectResponse|JsonResponse {
        // Manually fetch subcategory with trashed to support restore
        $subcategoryModel = Subcategory::withTrashed()
            ->where('id', $subcategory)
            ->where('category_id', $category->id)
            ->firstOrFail();

        $subcategoryModel->restore();

        return $this->respondWithSuccess(
            $request,
            'categories.edit',
            ['category' => $category->id],
            'Subcategory restored successfully.',
            (new SubcategoryResource($subcategoryModel->fresh()))->resolve()
        );
    }
}

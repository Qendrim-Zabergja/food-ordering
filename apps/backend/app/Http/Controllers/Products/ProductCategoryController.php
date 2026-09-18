<?php

namespace App\Http\Controllers\Products;

use App\Filters\ProductCategoryFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductCategoryRequest;
use App\Http\Requests\Products\UpdateProductCategoryRequest;
use App\Http\Resources\Products\ProductCategoryResource;
use App\Models\Products\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductCategoryController extends Controller
{
    public function index(Request $request, ProductCategoryFilters $filters): JsonResponse
    {
        $this->authorize('viewAny', ProductCategory::class);

        return ProductCategoryResource::collection(
            ProductCategory::filter($filters)
                ->withCount('products')
                ->paginate($request->integer('limit', 15))
        )->response();
    }

    public function show(ProductCategory $productCategory, Request $request): Response
    {
        $this->authorize('view', $productCategory);

        $productCategory = $this->loadRelationships($productCategory, $request);

        return response(new ProductCategoryResource($productCategory), 200);
    }

    public function store(StoreProductCategoryRequest $request): Response
    {
        $this->authorize('create', ProductCategory::class);

        $productCategory = ProductCategory::create($request->validated());

        $productCategory = $this->loadRelationships($productCategory, $request);

        return response(new ProductCategoryResource($productCategory), 201);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): Response
    {
        $this->authorize('update', $productCategory);

        $productCategory->update($request->validated());

        $productCategory = $this->loadRelationships($productCategory, $request);

        return response(new ProductCategoryResource($productCategory), 200);
    }

    public function destroy(ProductCategory $productCategory): Response
    {
        $this->authorize('delete', $productCategory);

        $productCategory->delete();

        return response(null, 204);
    }

    public function restore(string $uuid, Request $request): Response
    {
        $productCategory = ProductCategory::withTrashed()->where('uuid', $uuid)->firstOrFail();

        $this->authorize('restore', $productCategory);

        $productCategory->restore();

        $productCategory = $this->loadRelationships($productCategory, $request);

        return response(new ProductCategoryResource($productCategory), 200);
    }
}

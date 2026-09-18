<?php

namespace App\Http\Controllers\Products;

use App\Filters\ProductFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\Products\ProductResource;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request, ProductFilters $filters): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return ProductResource::collection(
            Product::filter($filters)->paginate($request->integer('limit', 15))
        )->response();
    }

    public function show(Product $product, Request $request): Response
    {
        $this->authorize('view', $product);

        $product = $this->loadRelationships($product, $request);

        return response(new ProductResource($product), 200);
    }

    public function store(StoreProductRequest $request): Response
    {
        $this->authorize('create', Product::class);

        $product = Product::create($this->attributes($request));

        $product = $this->loadRelationships($product, $request);

        return response(new ProductResource($product), 201);
    }

    public function update(UpdateProductRequest $request, Product $product): Response
    {
        $this->authorize('update', $product);

        $product->update($this->attributes($request));

        $product = $this->loadRelationships($product, $request);

        return response(new ProductResource($product), 200);
    }

    public function destroy(Product $product): Response
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response(null, 204);
    }

    public function restore(string $uuid, Request $request): Response
    {
        $product = Product::withTrashed()->where('uuid', $uuid)->firstOrFail();

        $this->authorize('restore', $product);

        $product->restore();

        $product = $this->loadRelationships($product, $request);

        return response(new ProductResource($product), 200);
    }

    /**
     * Translates the request payload into column values: the category arrives as
     * a uuid under `category.id` and the price in major units, while the table
     * stores an internal foreign key and cents.
     *
     * @return array<string, mixed>
     */
    protected function attributes(StoreProductRequest|UpdateProductRequest $request): array
    {
        $validated = $request->validated();
        $attributes = [];

        foreach (['name', 'description', 'image_url', 'is_available'] as $field) {
            if (array_key_exists($field, $validated)) {
                $attributes[$field] = $validated[$field];
            }
        }

        if (isset($validated['category']['id'])) {
            $attributes['product_category_id'] = ProductCategory::where('uuid', $validated['category']['id'])->value('id');
        }

        if (array_key_exists('price', $validated)) {
            $attributes['price_cents'] = (int) round($validated['price'] * 100);
        }

        return $attributes;
    }
}

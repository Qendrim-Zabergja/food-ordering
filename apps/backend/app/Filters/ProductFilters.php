<?php

namespace App\Filters;

use App\Models\Products\ProductCategory;

class ProductFilters extends QueryFilters
{
    public function search($term)
    {
        return $this->builder->where(function ($query) use ($term) {
            $query->where('name', 'LIKE', "%{$term}%")
                ->orWhere('description', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Filters by the category's uuid, never its internal id - the numeric id is
     * not something a client has ever been given.
     */
    public function category($uuid)
    {
        $categoryId = ProductCategory::where('uuid', $uuid)->value('id');

        return $this->builder->where('product_category_id', $categoryId);
    }

    public function is_available($value)
    {
        return $this->builder->where('is_available', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    public function price_from($value)
    {
        return $this->builder->where('price_cents', '>=', (int) round($value * 100));
    }

    public function price_to($value)
    {
        return $this->builder->where('price_cents', '<=', (int) round($value * 100));
    }

    public function trashed($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? $this->builder->withTrashed()
            : $this->builder;
    }
}

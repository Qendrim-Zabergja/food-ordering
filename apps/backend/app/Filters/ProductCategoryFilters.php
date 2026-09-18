<?php

namespace App\Filters;

class ProductCategoryFilters extends QueryFilters
{
    public function search($term)
    {
        return $this->builder->where(function ($query) use ($term) {
            $query->where('name', 'LIKE', "%{$term}%")
                ->orWhere('description', 'LIKE', "%{$term}%");
        });
    }

    public function is_active($value)
    {
        return $this->builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    public function trashed($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? $this->builder->withTrashed()
            : $this->builder;
    }
}

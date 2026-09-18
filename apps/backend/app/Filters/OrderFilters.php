<?php

namespace App\Filters;

use App\Enums\OrderStatus;

class OrderFilters extends QueryFilters
{
    public function search($term)
    {
        return $this->builder->where('order_number', 'LIKE', "%{$term}%");
    }

    public function status($value)
    {
        $statuses = array_filter(
            array_map(
                fn ($status) => OrderStatus::tryFrom((string) $status)?->value,
                (array) $value,
            ),
        );

        return $this->builder->whereIn('status', $statuses ?: ['__none__']);
    }

    public function placed_from($value)
    {
        return $this->builder->whereDate('placed_at', '>=', $value);
    }

    public function placed_to($value)
    {
        return $this->builder->whereDate('placed_at', '<=', $value);
    }

    public function trashed($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? $this->builder->withTrashed()
            : $this->builder;
    }
}

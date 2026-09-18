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

    /**
     * ?filter[placed_on]=today, or any date the database understands.
     *
     * "today" is resolved against the application timezone, so it means the
     * restaurant's day rather than the browser's.
     */
    public function placed_on($value)
    {
        $date = $value === 'today' ? now()->toDateString() : $value;

        return $this->builder->whereDate('placed_at', $date);
    }

    /**
     * ?filter[exclude_status][]=completed - the inverse of status().
     *
     * Kept as its own filter rather than folded into a single "active" flag:
     * "today" and "not finished" are two independent questions, and a caller
     * that wants only one of them should not have to take both.
     */
    public function exclude_status($value)
    {
        $statuses = array_filter(
            array_map(
                fn ($status) => OrderStatus::tryFrom((string) $status)?->value,
                (array) $value,
            ),
        );

        return $statuses === []
            ? $this->builder
            : $this->builder->whereNotIn('status', $statuses);
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

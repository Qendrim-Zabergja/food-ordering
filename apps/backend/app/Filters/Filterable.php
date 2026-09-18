<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Gives a model the filter() scope used by every index endpoint:
 *
 *     Product::filter($filters)->paginate($request->limit);
 */
trait Filterable
{
    public function scopeFilter(Builder $query, QueryFilters $filters): Builder
    {
        return $filters->apply($query);
    }
}

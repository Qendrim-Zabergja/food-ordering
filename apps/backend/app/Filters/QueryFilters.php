<?php

namespace App\Filters;

use App\Sorting\QuerySorting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Base class for every {Entity}Filters class.
 *
 * A subclass declares one public method per supported filter. The method name
 * is the query key, so ?filter[search]=pizza calls search('pizza'):
 *
 *     class ProductFilters extends QueryFilters
 *     {
 *         public function search($value)
 *         {
 *             $this->builder->where('name', 'LIKE', "%{$value}%");
 *         }
 *     }
 *
 * Anything without a matching method is ignored, so an unknown query key can
 * never widen a result set unintentionally.
 *
 * Supported query parameters:
 *   ?filter[key]=value    a declared filter method
 *   ?filter[exclude][]=   uuids to leave out
 *   ?with[]=relation      eager loads
 *   ?sort=name  /  !name  ascending / descending (see QuerySorting)
 */
class QueryFilters
{
    protected Request $request;

    protected Builder $builder;

    protected QuerySorting $sorting;

    public function __construct(Request $request, ?QuerySorting $sorting = null)
    {
        $this->request = $request;
        $this->sorting = $sorting ?? new QuerySorting($request);
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->includes() as $relation) {
            $this->builder = $this->builder->with($relation);
        }

        $filters = $this->filters();

        foreach ($filters as $name => $value) {
            if (! method_exists($this, $name)) {
                continue;
            }

            if (is_array($value) || strlen((string) $value) > 0) {
                $this->$name($value);
            }
        }

        if (isset($filters['exclude']) && is_array($filters['exclude'])) {
            $this->builder = $this->builder->whereNotIn('uuid', $filters['exclude']);
        }

        return $this->sorting->apply($this->builder);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter((array) $this->request->input('filter', []));
    }

    /**
     * @return array<int, string>
     */
    public function includes(): array
    {
        $with = $this->request->input('with', []);

        if (is_string($with)) {
            $with = explode(',', $with);
        }

        return array_values(array_filter(array_map('trim', (array) $with)));
    }
}

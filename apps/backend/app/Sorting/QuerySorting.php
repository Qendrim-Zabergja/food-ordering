<?php

namespace App\Sorting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Applies ?sort= to an index query.
 *
 *   ?sort=name      ascending
 *   ?sort=!name     descending, the ! prefix means reversed
 *   (omitted)       newest first
 *
 * A sort key is accepted only when it is a real column on the model's table,
 * or a method declared on a QuerySorting subclass for a computed sort. Anything
 * else is ignored rather than passed to the database.
 */
class QuerySorting
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $builder): Builder
    {
        $sort = $this->request->input('sort');

        if (! is_string($sort) || $sort === '') {
            return $builder->orderBy('created_at', 'desc');
        }

        $direction = 'asc';

        if (str_starts_with($sort, '!')) {
            $sort = substr($sort, 1);
            $direction = 'desc';
        }

        if (method_exists($this, $sort)) {
            return $this->$sort($builder, $direction);
        }

        $columns = Schema::getColumnListing($builder->getModel()->getTable());

        if (! in_array($sort, $columns, true)) {
            return $builder;
        }

        return $builder->orderBy($sort, $direction);
    }
}

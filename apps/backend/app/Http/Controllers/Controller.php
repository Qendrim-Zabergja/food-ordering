<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Throwable;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Load the relationships the client asked for through the `with` query
     * parameter, e.g. /api/products/{uuid}?with[]=category
     *
     * Controllers never call ->with() themselves. Every show, store and update
     * passes its model through here before returning it, so what a client may
     * eager load is decided in exactly one place.
     */
    protected function loadRelationships(Model $model, Request $request): Model
    {
        if (! $request->has('with')) {
            return $model;
        }

        $relations = $request->input('with');

        if (is_string($relations)) {
            $relations = explode(',', $relations);
        }

        if (! is_array($relations)) {
            return $model;
        }

        $relations = array_filter(array_map('trim', $relations));

        if (empty($relations)) {
            return $model;
        }

        try {
            $model->load($relations);
        } catch (Throwable) {
            // An unknown relation name in the query string is a client mistake,
            // not a server error. Return the model without it.
        }

        return $model;
    }
}

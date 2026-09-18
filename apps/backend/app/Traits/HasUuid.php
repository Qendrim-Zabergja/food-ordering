<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Every model in this application carries two identifiers:
 *
 *   id    bigIncrements  internal only, never leaves the application
 *   uuid  uuid           the external identifier, exposed as "id" in resources
 *
 * This trait owns the generation of that uuid. Never assign one by hand.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Route model binding resolves on uuid, so /api/products/{product}
     * takes a uuid and the numeric id is never guessable from a URL.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}

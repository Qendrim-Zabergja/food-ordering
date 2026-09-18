<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Parent of every resource in this application.
 *
 * The rule each subclass must follow: the internal numeric id never appears in
 * a response. Resources emit the uuid under the key "id":
 *
 *     return [
 *         'id'   => $this->uuid,
 *         'name' => $this->name,
 *         ...$this->timestamps(),
 *     ];
 *
 * Relationships are exposed through whenLoaded() only, so a resource never
 * triggers a query of its own.
 *
 * This class is also where a BROWSE/VIEW data split would live if this project
 * ever needs one - see the deviations section of CLAUDE.md.
 */
abstract class BaseResource extends JsonResource
{
    /**
     * @return array<string, string|null>
     */
    protected function timestamps(): array
    {
        return [
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

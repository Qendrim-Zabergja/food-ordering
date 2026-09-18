<?php

namespace App\Http\Resources;

class UserResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,

            // The frontend uses these to hide UI it should not offer. They are
            // a convenience, not a security boundary - every action is checked
            // again by a Policy on the server.
            'roles' => $this->roles->pluck('slug'),
            'permissions' => $this->permissionSlugs(),

            ...$this->timestamps(),
        ];
    }
}

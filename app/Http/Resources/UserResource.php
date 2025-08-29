<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'roles' => $this->resource->roles()->pluck('name'),
            'league' => $this->resource->league,
            'has_league' => (bool)$this->resource->league,
            'verified' => (bool)$this->resource->verified_at,
            'phone' => $this->resource->phone,
            'image' => $this->resource->image,
            'trail_ends_at' => $this->resource->trial_ends_at,
            'status' => $this->resource->status,
        ];
    }
}

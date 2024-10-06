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
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles()->pluck('name'),
            'league' => $this->league ?? 'No league assigned',
            'verified' => !!$this->email_verified_at,
            'phone' => $this->phone,
            'image' => $this->image,
        ];
    }
}

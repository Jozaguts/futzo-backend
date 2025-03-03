<?php

namespace App\Http\Resources;

use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Field */
class FieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'dimensions' => $this->dimensions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'location_id' => $this->location_id,

            'location' => new LocationResource($this->whenLoaded('location')),
        ];
    }
}

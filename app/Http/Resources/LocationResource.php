<?php

namespace App\Http\Resources;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Location */
class LocationResource extends JsonResource
{

    private array $imagesAvailable = ['fans', 'game-day', 'goal', 'junior-soccer'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'tags' => $this->tags->pluck('name'),
            'image' => $this->imagesAvailable[array_rand($this->imagesAvailable)],
        ];
    }
}

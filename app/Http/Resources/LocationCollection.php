<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\Location */
class LocationCollection extends ResourceCollection
{
    private array $imagesAvailable = ['fans', 'game-day', 'goal', 'junior-soccer'];

    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($location) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'tags' => $location->tags->pluck('name'),
                'image' => $this->imagesAvailable[array_rand($this->imagesAvailable)],
                'autocomplete_prediction' => $location->autocomplete_prediction
            ];
        })->toArray();
    }
}

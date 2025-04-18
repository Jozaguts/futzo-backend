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
            $availability = $location->fields->map(function ($field) {
                return $field->leaguesFields->map(fn($leagueField) => [
                    'id' => $field->id,
                    'name' => $field->name,
                    'type' => $field->type,
                    'isCompleted' => false,

                    ...$leagueField->availability
                ]);
            })->flatten(1)->toArray();
            return [
                'id' => $location->id,
                'name' => $location->name,
                'city' => $location->city,
                'address' => $location->address,
                'availability' => $availability,
                'fields_count' => $location->fields->count(),
                'position' => $location->position,
                'tags' => $location->tags->pluck('name'),
                'image' => $this->imagesAvailable[array_rand($this->imagesAvailable)],
                'autocomplete_prediction' => $location->autocomplete_prediction,
                'completed' => true,
            ];
        })->toArray();
    }
}

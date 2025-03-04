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
                'city' => $location->city,
                'address' => $location->address,
                'availability' => $location->fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'name' => $field->name,
                        'type' => $field->type,
                        'availability' => [
                            'leagues' => $field->leaguesFields->map(fn($leagueField) => $leagueField->availability),
                            'tournaments' => $field->tournamentsFields->map(fn($tournamentField) => $tournamentField->availability),
                        ]
                    ];
                }),
                'fields_count' => $location->fields->count(),
                'position' => $location->position,
                'tags' => [],
                'image' => $this->imagesAvailable[array_rand($this->imagesAvailable)],
                'autocomplete_prediction' => $location->autocomplete_prediction
            ];
        })->toArray();
    }
}

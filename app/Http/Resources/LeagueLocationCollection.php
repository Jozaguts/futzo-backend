<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LeagueLocationCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($location) use ($request) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'city' => $location->city,
                'position' => $location->position,
                'field_count' => $location->fields->count(),
                'fields' => $location->fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'name' => $field->name,
                        'type' => $field->type,
                        'dimensions' => $field->dimensions,
                        'availability' => $field->leaguesFields->map(function ($leagueField) {
                            return [
                                'league_id' => $leagueField->league_id,
                                'availability' => $leagueField->availability,
                            ];
                        }),
                    ];
                }),
            ];
        })->toArray();
    }
}

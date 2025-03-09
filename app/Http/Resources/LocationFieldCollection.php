<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LocationFieldCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($field, $key) {
            return [
                'id' => $field->id,
                'step' => ++$key,
                'name' => $field->name,
                'location_name' => $field->location->name,
                'availability' => $field->leaguesFields->map(function ($leagueField) {
                    return [...$leagueField->availability, 'isCompleted' => false];
                })->toArray()[0]
            ];
        })->toArray();
    }
}

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
                'field_id' => $field->id,
                'step' => ++$key,
                'field_name' => $field->name,
                'location_name' => $field->location->name,
                'location_id' => $field->location->id,
                'availability' => $field->leaguesFields->map(function ($leagueField) {
                    return [...$leagueField->availability, 'isCompleted' => false];
                })->toArray()[0]
            ];
        })->toArray();
    }
}

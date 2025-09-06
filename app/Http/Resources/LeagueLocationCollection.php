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
                'position' => $location->position,
                'field_count' => $location->fields->count(),
                'fields' => $location->fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'name' => $field->name,
                        'type' => $field->type,
                        'dimensions' => $field->dimensions,
                        'windows' => $field->leaguesFields->map(function ($leagueField) {
                            $byDay = $leagueField->windows
                                ->groupBy('day_of_week')
                                ->map(function ($items) {
                                    return $items->map(function ($w) {
                                        return [
                                            'start' => sprintf('%02d:%02d', intdiv($w->start_minute,60), $w->start_minute%60),
                                            'end' => sprintf('%02d:%02d', intdiv($w->end_minute,60), $w->end_minute%60),
                                        ];
                                    })->values()->toArray();
                                })->toArray();
                            return [
                                'league_id' => $leagueField->league_id,
                                'by_day' => $byDay,
                            ];
                        }),
                    ];
                }),
            ];
        })->toArray();
    }
}

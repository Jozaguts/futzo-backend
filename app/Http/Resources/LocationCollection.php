<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\Location */
class LocationCollection extends ResourceCollection
{
    private array $imagesAvailable = ['fans', 'game-day', 'goal', 'junior-soccer'];
    private const DOW_KEYS = [
        0 => 'sun',
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat',
    ];
    private const array DOW_LABELS = [
        0 => 'domingo',
        1 => 'lunes',
        2 => 'martes',
        3 => 'miércoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sábado',
    ];

    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($location) {
            $fields = $location->fields->map(function ($field) {
                // Tomamos las ventanas del primer league_field (asumido de la liga actual)
                $leagueField = $field->leaguesFields->first();
                $windows = [];
                if ($leagueField) {
                    foreach ($leagueField->windows as $w) {
                        $key = self::DOW_KEYS[$w->day_of_week] ?? null;
                        if (is_null($key)) { continue; }
                        $windows[$key] = $windows[$key] ?? [];
                        $windows[$key][] = [
                            'label' => self::DOW_LABELS[$w->day_of_week],
                            'enabled' => (bool) $w->enabled,
                            'start' => sprintf('%02d:%02d', intdiv($w->start_minute,60), $w->start_minute%60),
                            'end' => sprintf('%02d:%02d', intdiv($w->end_minute,60), $w->end_minute%60),
                        ];
                    }
                }
                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'windows' => (object) $windows,
                ];
            })->values()->toArray();
            return [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'fields' => $fields,
                'place_id' => $location->place_id,
                'fields_count' => $location->fields->count(),
                'position' => $location->position,
                'tags' => $location->tags->pluck('name'),
                'image' => $this->imagesAvailable[array_rand($this->imagesAvailable)],
                'completed' => true,
            ];
        })->toArray();
    }
}

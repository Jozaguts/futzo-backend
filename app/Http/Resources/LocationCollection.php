<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\Location */
class LocationCollection extends ResourceCollection
{
    private array $imagesAvailable = ['fans', 'game-day', 'goal', 'junior-soccer'];
    private const DOW_LABELS = [
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
            $windows = $location->fields->map(function ($field) {
                return $field->leaguesFields->map(function ($leagueField) use ($field) {
                    $byDay = $leagueField->windows
                        ->groupBy('day_of_week')
                        ->map(function ($items, $dow) {
                            $day = self::DOW_LABELS[$dow] ?? (string) $dow;
                            return $items->map(function ($w) use ($day) {
                                return [
                                    'day' => $day,
                                    'start' => sprintf('%02d:%02d', intdiv($w->start_minute,60), $w->start_minute%60),
                                    'end' => sprintf('%02d:%02d', intdiv($w->end_minute,60), $w->end_minute%60),
                                ];
                            })->values()->toArray();
                        })
                        ->values() // remover claves numéricas para mantener arreglo simple
                        ->toArray();
                    return [
                        'id' => $field->id,
                        'name' => $field->name,
                        'type' => $field->type,
                        'windows' => $byDay,
                    ];
                });
            })->flatten(1)->toArray();
            return [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'windows' => $windows,
                'fields_count' => $location->fields->count(),
                'position' => $location->position,
                'tags' => $location->tags->pluck('name'),
                'image' => $this->imagesAvailable[array_rand($this->imagesAvailable)],
                'completed' => true,
            ];
        })->toArray();
    }
}

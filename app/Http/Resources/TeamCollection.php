<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TeamCollection extends ResourceCollection
{
    protected mixed $categories;
    protected mixed $tournaments;

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($team) {
            $tournament = $team->tournaments()->where('team_id', $team->id)->first();
            $defaultHome = null;

            if ($tournament && $tournament->pivot) {
                $pivot = $tournament->pivot;
                $dayNames = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
                $defaultHome = [
                    'location_id' => $pivot->home_location_id,
                    'field_id' => $pivot->home_field_id,
                    'day_of_week' => $pivot->home_day_of_week,
                    'day_label' => is_null($pivot->home_day_of_week) ? null : $dayNames[$pivot->home_day_of_week] ?? null,
                    'start_time' => $pivot->home_start_time,
                ];
            }

            return [
                'id' => $team->id,
                'name' => $team->name,
                'address' => $team->address,
                'slug' => $team->slug,
                'description' => $team->description,
                'image' => $team->image,
                'president' => $team->president,
                'coach' => $team->coach,
                'category' => $team->categories()->where('team_id', $team->id)->first(),
                'tournament' => $tournament,
                'default_home' => $defaultHome,
                'colors' => $team->colors,
                'register_link' => $team->registerLink,
            ];
        })->toArray();
    }
}

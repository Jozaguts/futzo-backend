<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $tournament = $this->resource->tournaments()->first();
        $dayNames = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

        $defaultHome = null;
        if ($tournament && $tournament->pivot) {
            $pivot = $tournament->pivot;
            $defaultHome = [
                'location_id' => $pivot->home_location_id,
                'field_id' => $pivot->home_field_id,
                'day_of_week' => $pivot->home_day_of_week,
                'day_label' => is_null($pivot->home_day_of_week) ? null : $dayNames[$pivot->home_day_of_week] ?? null,
                'start_time' => $pivot->home_start_time,
            ];
        }

        $homeLocation = $this->resource->homeLocation;
        $homePreferences = [
            'location_id' => $this->resource->home_location_id,
            'location' => $homeLocation ? [
                'id' => $homeLocation->id,
                'name' => $homeLocation->name,
            ] : null,
            'day_of_week' => $this->resource->home_day_of_week,
            'day_label' => is_null($this->resource->home_day_of_week) ? null : $dayNames[$this->resource->home_day_of_week] ?? null,
            'start_time' => $this->resource->home_start_time,
        ];

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'colors' => $this->resource->colors,
            'image'=> $this->resource->image,
            'president' => $this->resource->president()->select('id', 'name', 'email', 'phone')->first(),
            'coach' => $this->resource->coach()->select('id', 'name', 'email', 'phone')->first(),
            'home_preferences' => $homePreferences,
            'tournament' => $tournament,
            'default_home' => $defaultHome,
            'category' => $this->resource->category()->select('id', 'name')->first(),
            'league' => $this->resource->leagues()->where('league_id', auth()?->user()?->league_id)->select('leagues.id', 'leagues.name')->first(),
            'slug' => $this->resource->slug,
        ];
    }
}

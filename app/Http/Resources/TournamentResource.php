<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $location = $this->resource->locations->first();
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'tournament_format_id' => $this->resource->tournament_format_id,
            'start_date' => $this->resource->start_date,
            'start_date_to_string' => $this->resource->start_date_to_string,
            'end_date' => $this->resource->end_date,
            'prize' => $this->resource->prize,
            'winner' => $this->resource->winner,
            'description' => $this->resource->description,
            'category_id' => $this->resource->category_id,
            'category' => $this->resource->category,
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'city' => $location->city,
                'address' => $location->address,
                'autocomplete_prediction' => $location->autocomplete_prediction,
            ],
            'format' => $this->resource->format,
            'teams_count' => $this->resource->teams_count,
            'players_count' => $this->resource->players_count,
            'games_count' => $this->resource->games_count,
            'image' => $this->resource->image,
            'thumbnail' => $this->resource->thumbnail,
            'status' => $this->resource->status,
            'league' => $this->resource->league,
            'max_teams' => optional($this->resource->configuration)->max_teams,
            'substitutions_per_team' => optional($this->resource->configuration)->substitutions_per_team,
        ];
    }
}

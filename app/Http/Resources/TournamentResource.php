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
            'id' => $this->id,
            'name' => $this->name,
            'tournament_format_id' => $this->tournament_format_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'prize' => $this->prize,
            'winner' => $this->winner,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'city' => $location->city,
                'address' => $location->address,
                'availability' => $location->availability,
                'autocomplete_prediction' => $location->autocomplete_prediction,
            ],
            'format' => $this->format,
            'teams_count' => $this->teams_count,
            'players_count' => $this->players_count,
            'games_count' => $this->games_count,
            'image' => $this->image,
            'thumbnail' => $this->thumbnail,
            'status' => $this->status,
        ];
    }
}

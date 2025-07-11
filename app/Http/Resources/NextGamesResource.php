<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class NextGamesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource?->id,
            'tournament' => $this->resource->tournament,
            'home_team' => [
                'id' => $this->resource->homeTeam?->id,
                'name' => $this->resource->homeTeam?->name,
                'image' => 'https://ui-avatars.com/api/?name=' . $this->resource->homeTeam?->name,
            ],
            'away_team' => [
                'id' => $this->resource->awayTeam?->id,
                'name' => $this->resource->awayTeam?->name,
                'image' => 'https://ui-avatars.com/api/?name=' . $this->resource->awayTeam?->name,
            ],
            'location' => [
                'id' => $this->resource->location?->id,
                'name' => $this->resource->location?->name,
            ],
            'field' => [
                'id' => $this->resource->field?->id,
                'name' => $this->resource->field?->name,
            ],
            'match_date' => $this->resource->match_date_to_string,
            'match_time' => $this->resource->match_time
        ];
    }
}

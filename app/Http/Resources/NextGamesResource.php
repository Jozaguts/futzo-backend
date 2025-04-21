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
            'id' => $this->id,
            'tournament' => $this->tournament,
            'home_team' => [
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
                'image' => 'https://ui-avatars.com/api/?name=' . $this->homeTeam->name,
            ],
            'away_team' => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
                'image' => 'https://ui-avatars.com/api/?name=' . $this->awayTeam->name,
            ],
            'location' => [
                'id' => $this->location->id,
                'name' => $this->location->name,
            ],
            'field' => [
                'id' => $this->field->id,
                'name' => $this->field->name,
            ],
            'match_date' => $this->match_date_to_string,
            'match_time' => $this->match_time->format('H:i'),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TournamentByLeagueCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($tournament) => [
                'id' => $tournament->id,
                "league_id" => $tournament->league_id,
                "category_id" => $tournament->category_id,
                "tournament_format_id" => $tournament->tournament_format_id,
                "football_type_id" => $tournament->football_type_id,
                'name' => $tournament->name,
                "image" => $tournament->image,
                "thumbnail" => $tournament->thumbnail,
                "prize" => $tournament->prize,
                "winner" => $tournament->winner,
                "description" => $tournament->description,
                'start_date' => $tournament->start_date,
                'end_date' => $tournament->end_date,
                'teams_count' => $tournament->teams_count,
                'players_count' => $tournament->players_count,
                'games_count' => $tournament->games_count,
                'status' => $tournament->status,
                'max_teams' => $tournament->configuration->max_teams,
                "min_teams" => $tournament->configuration->min_teams,
                'available_places' => $tournament->configuration->max_teams - $tournament->teams_count,
            ])->toArray(),
        ];
    }
}

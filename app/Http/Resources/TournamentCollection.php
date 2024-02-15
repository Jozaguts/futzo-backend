<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TournamentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn ($tournament) => [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'teams' => $tournament->teams_count,
            'players' => $tournament->players_count,
            'matches' => $tournament->games_count,
            'image' => $tournament->logo
        ])->toArray();
    }
}

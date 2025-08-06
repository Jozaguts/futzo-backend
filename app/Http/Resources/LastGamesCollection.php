<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\Game */
class LastGamesCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
       return $this->collection->map(fn ($game) => [
           'id' => $game->id,
           'homeTeam' => $game->homeTeam,
           'awayTeam' => $game->awayTeam,
           'home_goals' => $game->home_goals,
           'away_goals' => $game->away_goals,
           'status' => $game->status,
           'date' => $game->matchDateToString,
           'time' => $game->matchTime,
           'location' => $game->location,
           'field' => $game->field,
           'winner_team_id' =>  $game->winner_team_id,
       ])->toArray();
    }
}

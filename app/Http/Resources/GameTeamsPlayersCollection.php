<?php

namespace App\Http\Resources;

use App\Models\GameActionDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GameTeamsPlayersCollection extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'home' => [
                'team_id' => $this->resource->home_team_id,
                'name' => $this->resource->homeTeam->name,
                'players' => $this->resource->homeTeam->players->map(function ($player) {
                    $redCard = GameActionDetail::with('action')
                        ->where('game_id', $this->resource->id)
                        ->where('player_id', $player->id)
                        ->whereHas('action', function ($query) {
                            $query->whereIn('name', ['red card']);
                        })->exists();
                    $yellowCards = GameActionDetail::with('action')
                        ->where('game_id', $this->resource->id)
                        ->where('player_id', $player->id)
                        ->whereHas('action', function ($query) {
                            $query->whereIn('name', ['yellow card']);
                        })->count();
                    $dobleCard = $yellowCards === 2;
                    $cardType = match (true) {
                        $redCard => 'red-card',
                        $yellowCards === 1 => 'yellow-card',
                        $dobleCard => 'doble-card',
                        default => ''
                    };
                    return [
                        'id' => $player->id,
                        'name' => $player->user->name . ' ' . $player->user->last_name,
                        'position' => $player->position->abbr ?? 'MD',
                        '#' => $player->number ?? 10,
                        'goals' => $player->goals()->where('game_id', $this->resource->id)->count() ?? 0,
                        'cards' => $cardType,
                    ];
                })->toArray(),
            ],
            'away' => [
                'team_id' => $this->resource->away_team_id,
                'name' => $this->resource->awayTeam->name,
                'players' => $this->resource->awayTeam->players->map(function ($player) {
                    return [
                        'id' => $player->id,
                        'name' => $player->user->name . ' ' . $player->user->last_name,
                        'position' => $player->position,
                        '#' => $player->number,
                        'goals' => $player->goals()->where('game_id', $this->resource->id)->count() ?? 0,
                        'cards' => GameActionDetail::with('action')
                            ->where('game_id', $this->resource->id)
                            ->where('player_id', $player->id)
                            ->whereHas('action', function ($query) {
                                $query->whereIn('name', ['yellow card', 'red card']);
                            })
                            ->get()
                    ];
                })->toArray(),
            ],

        ];
    }
}

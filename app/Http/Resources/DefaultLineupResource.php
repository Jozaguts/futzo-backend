<?php

namespace App\Http\Resources;

use App\Models\DefaultLineup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DefaultLineup */
class DefaultLineupResource extends JsonResource
{
    private array $positions = [
        'goalkeeper' => 1,
        'defenses' => [2,3,4,5],
        'midfielders' => [6, 7, 8, 9],
        'forwards' => [10,11],
    ];

    public function toArray(Request $request): array
    {
        $formation = $this->resource->defaultLineup->formation;
        $this->positions['defenses'] = range(
            2,
            $formation->defenses + 1
        );
        $this->positions['midfielders'] = range(
            $formation->defenses + 2,
            $formation->defenses + $formation->midfielders + 1,
        );
        $this->positions['forwards'] = range(
            $formation->defenses + $formation->midfielders + 2,
            $formation->defenses + $formation->midfielders + $formation->forwards + 1,
        );
        return [
            'team_id' => $this->resource->id,
            'name' => $formation?->name ?? '',
            'goalkeeper' => $this->fillPlayers(
                $this->transformPlayers($this->positions['goalkeeper']),
                1
            ),
            'defenses' => $this->fillPlayers(
                $this->transformPlayers($this->positions['defenses']),
                $formation?->defenses ?? 0
            ),
            'midfielders' => $this->fillPlayers(
                $this->transformPlayers($this->positions['midfielders']),
                $formation?->midfielders ?? 0
            ),
            'forwards' => $this->fillPlayers(
                $this->transformPlayers($this->positions['forwards']),
                $formation?->forwards ?? 0
            ),
        ];
    }

    private function transformPlayers($positionIds)
    {
        $players = $this->resource->defaultlineup->players()->whereHas('defaultLineup', function ($query) use ($positionIds) {
            $query->whereIn('field_location', (array) $positionIds);
        })->get();

        return $players->map(function ($player) {
            $defaultLineupPlayers = $this->resource->defaultlineup->defaultLineupPlayers()->where('player_id', $player->id)?->first();
            return [
                'default_lineup_player_id' => $defaultLineupPlayers->id ?? null,
                'field_location' => $defaultLineupPlayers->field_location ?? null,
                 'abbr' => $player?->position?->abbr ?? '',
                 'number' => $player?->number ?? 0,
                 'name' => $player?->user?->name ?? '',
                 'goals' => 0,
                 'cards' => [
                     'red' => false,
                     'yellow' => false,
                     'doble_yellow_card' => false
                 ],
                 'substituted' => false
         ];
        })->values()->all();
    }

    private function fillPlayers(array $players, int $total): array
    {
        $empty = $this->emptyPlayer();
        while (count($players) < $total) {
            $players[] = $empty;
        }
        return array_slice($players, 0, $total);
    }

    private function emptyPlayer(): array
    {
        return [
            'abbr' => '',
            'number' => 0,
            'name' => '',
            'goals' => 0,
            'cards' => [
                'red' => false,
                'yellow' => false,
                'doble_yellow_card' => false
            ],
            'substituted' => false
        ];
    }
}
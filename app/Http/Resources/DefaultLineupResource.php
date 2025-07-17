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
        'defenses' => [2, 3, 4, 5, 6, 7, 8],
        'midfielders' => [9, 10, 11, 14, 15],
        'forwards' => [12, 13, 16, 17, 18]
    ];

    public function toArray(Request $request): array
    {
        $formation = $this->resource->defaultLineup->formation;

        return [
            'team_id' => $this->resource->id,
            'formation' => $formation?->name ?? '',
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
        $players = $this->resource->defaultlineup->players->whereIn(
            'position_id',
            (array) $positionIds
        );

        return $players->map(fn($player) => [
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
        ])->values()->all();
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
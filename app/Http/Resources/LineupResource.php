<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LineupResource extends JsonResource
{
    private array $positions = [
        'goalkeeper' => 1,
        'defenses' => [2,3,4,5],
        'midfielders' => [6, 7, 8, 9],
        'forwards' => [10,11],
    ];

    public function toArray(Request $request): array
    {
        $formation = $this->resource->formation; // resource means Lineup model

        // Si no hay formación, devolvemos campos vacíos
        if (!$formation) {
            return [
                'team_id' => $this->resource->team_id,
                'name' => '',
                'goalkeeper' => [$this->emptyPlayer(1)],
                'defenses' => [],
                'midfielders' => [],
                'forwards' => [],
            ];
        }

        // Asignamos rangos de posición en base a la formación
        $this->positions['defenses'] = range(2, $formation->defenses + 1);
        $this->positions['midfielders'] = range(
            $formation->defenses + 2,
            $formation->defenses + $formation->midfielders + 1
        );
        $this->positions['forwards'] = range(
            $formation->defenses + $formation->midfielders + 2,
            $formation->defenses + $formation->midfielders + $formation->forwards + 1
        );

        return [
            'team_id' => $this->resource->team_id,
            'team' => [
                'id' => $this->resource->team_id,
                'name' => $this->resource->team?->name ?? '',
                'image' => $this->resource->team?->image ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->resource->team?->name) . '&background=' . str_replace('#', '', $this->resource->team_color),
            ],
            'players' => $this->resource
                ->lineupPlayers()
                ->where('is_headline', false)
                ->with('player.user')
                ->get()
                ->map(function ($lnp) {
                    return [
                        'player_id' => $lnp->player->id,
                        'team_id' => $lnp->player->team_id,
                        'name' => $lnp->player->user?->name ?? '',
                        'number' => $lnp->player->number,
                        'position' => $lnp->player->position?->abbr ?? '',
                    ];
                })->values(),
            'name' => $formation->name,
            'goalkeeper' => $this->fillPlayers(
                $this->transformPlayers($this->positions['goalkeeper']),
                1,
                (array) $this->positions['goalkeeper']
            ),
            'defenses' => $this->fillPlayers(
                $this->transformPlayers($this->positions['defenses']),
                $formation->defenses,
                $this->positions['defenses']
            ),
            'midfielders' => $this->fillPlayers(
                $this->transformPlayers($this->positions['midfielders']),
                $formation->midfielders,
                $this->positions['midfielders']
            ),
            'forwards' => $this->fillPlayers(
                $this->transformPlayers($this->positions['forwards']),
                $formation->forwards,
                $this->positions['forwards']
            ),
        ];
    }

    /**
     * Transforms the players in the lineup based on field_location.
     */
    private function transformPlayers($positionIds)
    {
        $players = $this->resource->lineupPlayers()
            ->where('is_headline', true)
            ->whereIn('field_location', (array) $positionIds)
            ->with(['player.user', 'player.position']) // eager load to avoid N+1
            ->get();

        return $players->map(function ($lineupPlayer) {
            $player = $lineupPlayer->player;

            return [
                'lineup_player_id' => $lineupPlayer->id,
                'field_location' => $lineupPlayer->field_location,
                'abbr' => $player?->position?->abbr ?? '',
                'number' => $player?->number ?? 0,
                'name' => $player?->user?->name ?? '',
                'goals' => $lineupPlayer->goals ?? 0,
                'color' => $this->resource->team_color,
                'cards' => [
                    'red' => (bool) $lineupPlayer->red_card,
                    'yellow' => (bool) $lineupPlayer->yellow_card,
                    'doble_yellow_card' => (bool) $lineupPlayer->doble_yellow_card
                ],
                'substituted' => $lineupPlayer->substituted ?? false
            ];
        })->values()->all();
    }

    /**
     * Rellena los huecos si faltan jugadores en la posición.
     */
    private function fillPlayers(array $players, int $total, array $positionRange): array
    {
        $usedFieldLocations = collect($players)
            ->pluck('field_location')
            ->filter()
            ->values()
            ->all();

        $availableFieldLocations = collect($positionRange)
            ->diff($usedFieldLocations)
            ->values()
            ->all();

        foreach ($availableFieldLocations as $fieldLocation) {
            if (count($players) >= $total) {
                break;
            }
            $players[] = $this->emptyPlayer($fieldLocation);
        }

        return array_slice($players, 0, $total);
    }

    /**
     * Devuelve un jugador vacío, pero con `field_location` correcto.
     */
    private function emptyPlayer(?int $fieldLocation): array
    {
        return [
            'default_lineup_player_id' => null,
            'field_location' => $fieldLocation,
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

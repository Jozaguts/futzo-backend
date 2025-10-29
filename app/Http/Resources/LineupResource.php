<?php

namespace App\Http\Resources;

use App\Models\DefaultLineupPlayer;
use App\Models\GameEvent;
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
        // resource means Lineup model
        $formation = $this->resource->formation;

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

         /**  according to the formation value indicates, we assign the ranges of locations for each type of position
          * * |1|  the first location has to be for the goalkeeper
          * * |2| |3| |4| |5|  defenses
          * * |6| |7| |8| |9| midfielders
          * * |10| |11|  forwards
          * * so, the goalkeeper is position 1, we need to start in the position 2 and ranged the amount of spaces that the formation indicates E.G.
          * if teh formation is 4 - 4 - 2 the defenses need 4 spaces starting in the 2 and move $formation->defenses plus 1
          * the result of that is 'defenses' => [2,3,4,5], the same is for midfielders and forwards
          * for the 4-4-2 formation the result of range each position its go to be
          * $positions = [ 'goalkeeper' => 1, 'defenses' => [2,3,4,5], 'midfielders' => [6, 7, 8, 9], 'forwards' => [10,11] ];
        * */

        $this->positions['defenses'] = range(2, $formation->defenses + 1); // for 4-4-4 the result is: [2,3,4,5]
        $this->positions['midfielders'] = range(
            $formation->defenses + 2,
            $formation->defenses + $formation->midfielders + 1
        );  // for 4-4-4 the result is: [6, 7, 8, 9]
        $this->positions['forwards'] = range(
            $formation->defenses + $formation->midfielders + 2,
            $formation->defenses + $formation->midfielders + $formation->forwards + 1
        ); // for 4-4-2 the result is:  [10,11]

        // Determinamos si la alineación proviene de una plantilla base para decidir qué jugadores mostrar en la banca.
        $hasDefaultLineupPlayers = $this->hasDefaultLineupPlayers();
        $availablePlayers = $hasDefaultLineupPlayers
            ? $this->availableBenchPlayers()
            : $this->teamRosterPlayers();

        return [
            'team_id' => $this->resource->team_id,
            'team' => [
                'id' => $this->resource->team_id,
                'name' => $this->resource->team?->name ?? '',
                'image' => $this->resource->team?->image,
            ],
            'players' => $availablePlayers,
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
                'goals' => $lineupPlayer->player->gameEvents()->whereIn('type',[GameEvent::GOAL, GameEvent::PENALTY])->count() ?? 0,
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

    private function hasDefaultLineupPlayers(): bool
    {
        if (!$this->resource->default_lineup_id) {
            return false;
        }

        return DefaultLineupPlayer::where('default_lineup_id', $this->resource->default_lineup_id)->exists();
    }

    private function availableBenchPlayers(): array
    {
        return $this->resource
            ->lineupPlayers()
            ->where('is_headline', false)
            ->with('player.user', 'player.position')
            ->get()
            ->map(function ($lnp) {
                return [
                    'player_id' => $lnp->player->id,
                    'team_id' => $lnp->player->team_id,
                    'name' => $lnp->player->user?->name ?? '',
                    'number' => $lnp->player->number,
                    'position' => $lnp->player->position?->abbr ?? '',
                ];
            })
            ->values()
            ->all();
    }

    private function teamRosterPlayers(): array
    {
        if (!$this->resource->team) {
            return [];
        }

        return $this->resource
            ->team
            ->players()
            ->with('user', 'position')
            ->get()
            ->map(function ($player) {
                return [
                    'player_id' => $player->id,
                    'team_id' => $player->team_id,
                    'name' => $player->user?->name ?? '',
                    'number' => $player->number,
                    'position' => $player->position?->abbr ?? '',
                ];
            })
            ->unique('player_id')
            ->values()
            ->all();
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

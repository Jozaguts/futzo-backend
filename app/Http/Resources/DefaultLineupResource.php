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
        // calculate the range of position for defenses, midfielders and forwards
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
                1,
                (array) $this->positions['goalkeeper']
            ),
            'defenses' => $this->fillPlayers(
                $this->transformPlayers($this->positions['defenses']),
                $formation?->defenses ?? 0,
                $this->positions['defenses']
            ),
            'midfielders' => $this->fillPlayers(
                $this->transformPlayers($this->positions['midfielders']),
                $formation?->midfielders ?? 0,
                $this->positions['midfielders']
            ),
            'forwards' => $this->fillPlayers(
                $this->transformPlayers($this->positions['forwards']),
                $formation?->forwards ?? 0,
                $this->positions['forwards']
            ),
        ];
    }
    /**
     * Transforms players based on their position IDs.
     *
     * This method retrieves players associated with the given position IDs
     * and maps them into a structured array format. Each player is enriched
     * with additional details such as their position abbreviation, number,
     * name, and default lineup information. Default values are provided for
     * fields like goals, cards, and substitution status.
     *
     * @param array|int $positionIds The position IDs to filter players by.
     * @return array An array of transformed players with detailed information.
     */
    private function transformPlayers($positionIds)
    {
        // Retrieve players associated with the given position IDs.
        $players = $this->resource->defaultlineup->players()->whereHas('defaultLineup', function ($query) use ($positionIds) {
            $query->whereIn('field_location', (array) $positionIds);
        })->get();
        // Map each player to a structured array with additional details.
        return $players->map(function ($player) {
            // Retrieve the default lineup player information for the current player.
            $defaultLineupPlayers = $this->resource->defaultlineup->defaultLineupPlayers()->where('player_id', $player->id)?->first();
            // Return the transformed player data.
            return [
                'default_lineup_player_id' => $defaultLineupPlayers?->id,
                'field_location' => $defaultLineupPlayers?->field_location,// Field location of the player. "THIS INDICATES THE POSITION ON DE FRONTEND",
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

    /**
     * Fills the player array to match the required total number of players.
     *
     * This method ensures that the provided array of players is filled with
     * placeholder players (using `emptyPlayer`) until the desired total number
     * of players is reached. It also ensures that the field locations for the
     * placeholder players are within the specified position range.
     *
     * @param array $players The current array of players.
     * @param int $total The total number of players required.
     * @param array $positionRange The range of valid field locations for the players.
     * @return array The array of players, filled to the required total.
     */
    private function fillPlayers(array $players, int $total, array $positionRange): array
    {
        // Extract the field locations already used by the current players.
        $usedFieldLocations = collect($players)
            ->pluck('field_location')
            ->filter()
            ->values()
            ->all();
        // Determine the available field locations by subtracting used locations from the position range.
        $availableFieldLocations = collect($positionRange)
            ->diff($usedFieldLocations)
            ->values()
            ->all();
        // Add placeholder players for each available field location until the total is reached.
        foreach ($availableFieldLocations as $fieldLocation) {
            if (count($players) >= $total) {
                break; // Stop if the required total number of players is reached.
            }
            $players[] = $this->emptyPlayer($fieldLocation); // Add a placeholder player.
        }
        // Return the array of players, limited to the required total number.
        return array_slice($players, 0, $total);
    }

    /**
     * Creates an empty player array with default values.
     *
     * This method is used to generate a placeholder player object
     * for a specific field location. It ensures that all required
     * fields are populated with default values, such as null, 0, or
     * empty strings, depending on the field type.
     *
     * @param int|null $fieldLocation The field location for the empty player.
     *                                This can be null if no specific location is provided.
     * @return array An associative array representing an empty player with default values.
     */
    private function emptyPlayer(?int $fieldLocation): array
    {
        return [
            'default_lineup_player_id' =>  null,
            'field_location' =>  $fieldLocation,
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
<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Http\Resources\GameTeamsPlayersCollection;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GameController extends Controller
{
    public const TWO_HOURS = 120;
    public const ONE_HOUR = 60;

    public function show(int $gameId): GameResource
    {
        $game = Game::with(["tournament.locations.fields"])->findOrFail($gameId);
        return new GameResource($game);
    }

    public function update(Request $request, Game $game): GameResource
    {
        $data = $request->validate([
            'date' => 'required|date',
            'selected_time' => 'required|array',
            'selected_time.start' => 'required|date_format:H:i',
            'selected_time.end' => 'required|date_format:H:i',
            'field_id' => 'required|exists:fields,id',
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        // 1) Datos iniciales
        $day = strtolower($data['day']);
        $oldStart = substr($game->match_time, 0, 5);
        $stepMins = $game->tournament->configuration->game_time
            + $game->tournament->configuration->time_between_games
            + 15 + 15; // total 120 minutos (2 horas) dependiendo de la configuracion del torneo
        $oldNext = Carbon::parse($oldStart)->addMinutes($stepMins)->format('H:i');
        $newStart = $data['selected_time']['start'];
        $newEnd = Carbon::parse($data['selected_time']['end'])->subMinutes($stepMins)->format('H:i');

        // 2) Cargar el pivot completo
        $tournField = $game->tournament
            ->tournamentFields()
            ->where('field_id', $game->field_id)
            ->firstOrFail();

        // 3) Leer todo el array availability
        $availability = $tournField->availability;

        // 4) Tomar solo los intervals de ese día
        $intervals = $availability[$day]['intervals'] ?? [];

        // 5) Recorrer por referencia y liberar/ocupar bloques completos
        foreach ($intervals as &$interval) {
            // liberar los antiguos
            if ($interval['value'] === $oldStart || $interval['value'] === $oldNext) {
                $interval['selected'] = false;
                $interval['in_use'] = false;
            }
            // marcar los nuevos
            if ($interval['value'] === $newStart || $interval['value'] === $newEnd) {
                $interval['selected'] = true;
                $interval['in_use'] = true;
            }
        }
        unset($interval);

        // 6) Asignar de vuelta el array modificado
        $availability[$day]['intervals'] = $intervals;
        $tournField->availability = $availability;
        $tournField->save();

        // 7) Finalmente actualizar la tabla games
        $game->update([
            'match_date' => Carbon::parse($data['date'])->toDateString(),
            'match_time' => $newStart . ':00',
            'field_id' => (int)$data['field_id'],
        ]);

        return new GameResource($game);
    }

    public function teamsPlayers(int $gameId)
    {
        $game = Game::where('id', $gameId)
            ->with([
                'homeTeam' => function ($query) {
                    $query->select(['id', 'name'])
                        ->with([
                            'players' => function ($query) {
                                $query->select(['id', 'team_id', 'user_id'])
                                    ->with('user:id,name,last_name');
                            }
                        ]);
                },
                'awayTeam' => function ($query) {
                    $query->select(['id', 'name'])
                        ->with([
                            'players' => function ($query) {
                                $query->select(['id', 'team_id', 'user_id'])
                                    ->with('user:id,name,last_name');
                            }
                        ]);
                }
            ])
            ->firstOrFail();
        return GameTeamsPlayersCollection::make($game);
    }

}

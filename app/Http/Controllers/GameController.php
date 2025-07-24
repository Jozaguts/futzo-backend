<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Http\Resources\GameTeamsPlayersCollection;
use App\Http\Resources\LineupResource;
use App\Models\DefaultLineup;
use App\Models\Formation;
use App\Models\Game;
use App\Models\Lineup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GameController extends Controller
{
    public const int TWO_HOURS = 120;
    public const int ONE_HOUR = 60;

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
        $oldDay = strtolower($game->match_date ? Carbon::parse($game->match_date)->format('l') : $day);
        $oldDay = strtolower($oldDay);
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

        // 3) Leer el array availability
        $availability = $tournField->availability;

        // 4) Tomar solo los intervals de ese día
        $intervals = $availability[$day]['intervals'] ?? [];

        // 5) Recorrer por referencia y liberar/ocupar bloques completos
        if ($oldDay !== $day) {
            $oldIntervals = $availability[$oldDay]['intervals'] ?? [];
            foreach ($oldIntervals as &$interval) {
                if ($interval['value'] === $oldStart || $interval['value'] === $oldNext) {
                    $interval = [
                        'value' => $interval['value'],
                        'in_use' => false,
                        'selected' => false,
                    ];
                }
            }
            unset($interval);
            $availability[$oldDay]['intervals'] = $oldIntervals;

            $tournField->availability = $availability;
            $tournField->save();
        }

        $availability[$day]['intervals'] = $intervals;
        $tournField->availability = $availability;
        $tournField->save();


        foreach ($intervals as &$interval) {
            // liberar los antiguos
            if ($interval['value'] === $oldStart || $interval['value'] === $oldNext) {
                $interval = [
                    'value' => $interval['value'],
                    'in_use' => false, // liberar el bloque
                    'selected' => true, // desmarcar el bloque
                ];
            }

            if ($interval['value'] === $newStart || $interval['value'] === $newEnd) {

                $interval = [
                    'value' => $interval['value'],
                    'in_use' => true, // ocupar el bloque
                    'selected' => true, // marcar el bloque
                ];
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

    public function teamsPlayers(int $gameId): GameTeamsPlayersCollection
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

    public function formations(): JsonResponse
    {
        $formations = Formation::all();
        return response()->json($formations);
    }
    public function initializeReport(Game $game): JsonResponse
    {
        $homeLineup = $this->getOrCreateLineup($game, $game->home_team_id);
        $awayLineup = $this->getOrCreateLineup($game, $game->away_team_id);

        return response()->json([
            'home' => new LineupResource($homeLineup),
            'away' => new LineupResource($awayLineup),
        ]);
    }

    private function getOrCreateLineup(Game $game, int $teamId): Lineup
    {
        $lineup = Lineup::where('game_id', $game->id)->where('team_id', $teamId)->first();

        if ($lineup) {
            return $lineup;
        }

        $defaultLineup = DefaultLineup::where('team_id', $teamId)->with('defaultLineupPlayers')->first();

        $formation = $defaultLineup?->formation;

        // Crear la lineup vacía
        $lineup = Lineup::create([
            'game_id' => $game->id,
            'team_id' => $teamId,
            'formation_id' => $formation?->id,
            'default_lineup_id' => $defaultLineup?->id,
            'round' => $game->round,
        ]);

        // Si hay jugadores en default, clonarlos
        if ($defaultLineup) {
            foreach ($defaultLineup->defaultLineupPlayers as $defaultPlayer) {
                $lineup->lineupPlayers()->create([
                    'player_id' => $defaultPlayer->player_id,
                    'field_location' => $defaultPlayer->field_location,
                    'substituted' => false,
                    'goals' => 0,
                    'yellow_card' => false,
                    'red_card' => false,
                    'doble_yellow_card' => false,
                ]);
            }
        }

        return $lineup;
    }

    public function getPlayers(Game $game): JsonResponse
    {
       $game->load([
           'homeTeam.players.user:id,name,last_name',
           'awayTeam.players.user:id,name,last_name',
           'lineups.lineupPlayers'
       ]); // una sola consulta para cargar los jugadores de ambos equipos y sus alineaciones

       $homeTeamId = $game->home_team_id;
       $awayTeamId = $game->away_team_id;

       $homeLineup = $game->lineups->firstWhere('team_id', $homeTeamId);
       $awayLineup = $game->lineups->firstWhere('team_id', $awayTeamId);

       $homePlayersIds = $homeLineup?->lineupPlayers->pluck('player_id');
       $awayPlayersIds = $awayLineup?->lineupPlayers->pluck('player_id');

       $homePlayers = $game->homeTeam->players;
       $awayPlayers = $game->awayTeam->players;

       $homeHeadlines = $homePlayers->whereIn('id', $homePlayersIds);
       $homeSubstitutes = $homePlayers->whereNotIn('id', $homePlayersIds);

       $awayHeadlines = $awayPlayers->whereIn('id', $awayPlayersIds);
       $awaySubstitutes = $awayPlayers->whereNotIn('id', $awayPlayersIds);

       return response()->json([
           'home' =>[
               'headlines' => $homeHeadlines->values(),
               'substitutes' => $homeSubstitutes->values(),
           ],
           'away' => [
               'headlines' => $awayHeadlines->values(),
               'substitutes' => $awaySubstitutes->values(),
           ]
       ]);
    }
}

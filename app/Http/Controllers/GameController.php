<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubstitutionRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\GameTeamsPlayersCollection;
use App\Http\Resources\LineupResource;
use App\Models\DefaultLineup;
use App\Models\Formation;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\Substitution;
use App\Models\Team;
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
                            },
                            'teamEvents' => function ($query) {
                                $query->whereIn('type', [
                                    GameEvent::YELLOW_CARD,
                                    GameEvent::RED_CARD,
                                ]);
                            }
                        ]);
                },
                'awayTeam' => function ($query) {
                    $query->select(['id', 'name'])
                        ->with([
                            'players' => function ($query) {
                                $query->select(['id', 'team_id', 'user_id'])
                                    ->with('user:id,name,last_name');
                            },
                            'teamEvents' => function ($query) {
                                $query->whereIn('type', [
                                    GameEvent::YELLOW_CARD,
                                    GameEvent::RED_CARD,
                                ]);
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
            $lineup->setAttribute('team_color', $lineup->team->colors['home']['primary']);
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
        $players = Team::findOrFail($teamId)->players;
        $players->map(fn($player, $key) => $lineup->lineupPlayers()->save(
            LineupPlayer::updateOrCreate([
                'lineup_id' => $lineup->id,
                'player_id' => $player->id,
            ], [
                'is_headline' => false,
                'field_location' => null,
                'substituted' => false,
                'goals' => 0,
                'yellow_card' => false,
                'red_card' => false,
                'doble_yellow_card' => false,
            ])
        ));
        if ($defaultLineup) {
            foreach ($defaultLineup->defaultLineupPlayers as $data) {
                $lineup->lineupPlayers()->save(
                    LineupPlayer::updateOrCreate([
                        'lineup_id' => $lineup->id,
                        'player_id' => $data->player_id,
                    ], [
                        'is_headline' => false,
                        'field_location' => $data->field_location,
                        'substituted' => false,
                        'goals' => 0,
                        'yellow_card' => false,
                        'red_card' => false,
                        'doble_yellow_card' => false,
                    ])
                );
            }
        }
        $lineup->setAttribute('team_color', $lineup->team->colors['home']['primary']);
        return $lineup;
    }

    public function getPlayers(Game $game): JsonResponse
    {
        $game->load([
            'homeTeam',
            'awayTeam',
            'lineups.lineupPlayers.player.user:id,name,last_name',
            'substitutions:id,game_id,team_id,player_in_id,player_out_id,minute',
        ]);

        $homeLineup = $game->lineups->firstWhere('team_id', $game->home_team_id);
        $awayLineup = $game->lineups->firstWhere('team_id', $game->away_team_id);
        $homeSubstitutions = $game->substitutions->where('team_id', $game->home_team_id)->values();
        $awaySubstitutions = $game->substitutions->where('team_id', $game->away_team_id)->values();

        return response()->json([
            'home' => [
                'headlines' => $homeLineup?->lineupPlayers->where('is_headline', true)->pluck('player')->values(),
                'substitutes' => $homeLineup?->lineupPlayers->where('is_headline', false)->pluck('player')->values(),
                'substitutions' => $homeSubstitutions,
            ],
            'away' => [
                'headlines' => $awayLineup?->lineupPlayers->where('is_headline', true)->pluck('player')->values(),
                'substitutes' => $awayLineup?->lineupPlayers->where('is_headline', false)->pluck('player')->values(),
                'substitutions' => $awaySubstitutions,
            ],
        ]);
    }

    public function substitutions(SubstitutionRequest $request, Game $game): JsonResponse
    {
        $data = $request->validated();
        foreach( ['home' => $game->home_team_id, 'away' => $game->away_team_id] as $key => $teamId)  {
            if (!empty($data[$key])) {
                foreach ($data[$key] as $substitution) {
                    Substitution::updateOrCreate([
                        'game_id' => $game->id,
                        'team_id' => $teamId,
                        'player_in_id' => $substitution['player_in_id'],
                        'player_out_id' => $substitution['player_out_id'],
                    ],[
                        'minute' => $substitution['minute']
                    ]);

                    $game->lineups()
                        ->where('team_id', $teamId)
                        ->firstOrFail()
                        ->lineupPlayers()
                        ->where('player_id', $substitution['player_out_id'])
                        ->update(['substituted' => true]);

                    GameEvent::create([
                        'game_id' => $game->id,
                        'type' => GameEvent::SUBSTITUTION,
                        'minute' => $substitution['minute'],
                        'player_id' => $substitution['player_out_id'],
                        'related_player_id' => $substitution['player_in_id'],
                        'team_id' => $teamId,
                    ]);
                }
            }
        }
        return response()->json([
            'message' => 'Substitución registrada correctamente.']);
    }
    public function destroySubstitution(Game $game, Substitution $substitution): JsonResponse
    {
        $substitution->delete();
        $game->lineups()
            ->where('team_id', $substitution->team_id)
            ->firstOrFail()
            ->lineupPlayers()
            ->where('player_id', $substitution->player_out_id)
            ->update(['substituted' => false]);
        GameEvent::where('game_id', $game->id)
            ->where('type', GameEvent::SUBSTITUTION)
            ->where('player_id', $substitution->player_out_id)
            ->where('related_player_id', $substitution->player_in_id)
            ->delete();

        return response()->json([
            'message' => 'Substitución eliminada correctamente.'
        ]);
    }
    public function cards(Request $request, Game $game): JsonResponse
    {
        $data = $request->validate([
            'home' => 'array',
            'away' => 'array',
        ]);

        foreach (['home' => $game->home_team_id, 'away' => $game->away_team_id] as $key => $teamId) {
            if (!empty($data[$key])) {
                foreach ($data[$key] as $card) {
                   GameEvent::updateOrCreate([
                        'game_id' => $game->id,
                        'type' => $card['type'],
                        'minute' => $card['minute'],
                        'player_id' => $card['player_id'],
                        'team_id' => $teamId,
                    ], [
                        'related_player_id' => null, // No se usa en tarjetas
                    ]);

                }
            }
        }

        return response()->json(['message' => 'Tarjetas actualizadas correctamente.']);
    }
    public function destroyCardGameEvent(Game $game, GameEvent $card): JsonResponse
    {
        $card->delete();
        return response()->json(['message' => 'Tarjeta eliminada correctamente.']);
    }

    public function goals (Request $request, Game $game): JsonResponse
    {
        $data = $request->validate([
            'home' => 'array',
            'away' => 'array',
        ]);

        foreach (['home' => $game->home_team_id, 'away' => $game->away_team_id] as $key => $teamId) {
            if (!empty($data[$key])) {
                foreach ($data[$key] as $goal) {
                    if ($goal['type'] === GameEvent::OWN_GOAL) {
                        GameEvent::updateOrCreate([
                            'game_id' => $game->id,
                            'type' => GameEvent::OWN_GOAL,
                            'minute' => $goal['minute'],
                            'player_id' => $goal['player_id'],
                            'team_id' => $teamId,
                        ], [
                            'related_player_id' => null,
                        ]);
                        if ($key === 'home'){
                            ++$game->away_goals; // Si es un autogol, se suma al equipo contrario
                        } else {
                            ++$game->home_goals; // Si es un autogol, se suma al equipo contrario
                        }
                    }else if($goal['type'] === GameEvent::GOAL ||  $goal['type'] === GameEvent::PENALTY) {
                        GameEvent::updateOrCreate([
                            'game_id' => $game->id,
                            'type' => $goal['type'],
                            'minute' => $goal['minute'],
                            'player_id' => $goal['player_id'],
                            'team_id' => $teamId,
                        ], [
                            'related_player_id' => $goal['related_player_id'],
                        ]);
                        if ($key === 'home' ){
                            ++$game->home_goals;
                        }else if($key === 'away'){
                            ++$game->away_goals;
                        }
                    }

                    $game->save();

                }
            }
        }

        return response()->json(['message' => 'Goles actualizados correctamente.']);
    }
    public function destroyGoalGameEvent(Game $game, GameEvent $gameEvent): JsonResponse
    {
        logger('Eliminando gol', [
            'game_id' => $game->id,
            'goal_id' => $gameEvent->id,
            'goal_type' => $gameEvent->type,
            'team_id' => $gameEvent->team_id,
        ]);
        if ($gameEvent->type === GameEvent::OWN_GOAL) {
            if ($gameEvent->team_id === $game->home_team_id) {
                --$game->away_goals; // Si es un autogol, se resta al equipo contrario
            } else {
                --$game->home_goals; // Si es un autogol, se resta al equipo contrario
            }
        } else if ($gameEvent->type === GameEvent::GOAL || $gameEvent->type === GameEvent::PENALTY) {
            if ($gameEvent->team_id === $game->home_team_id) {
                --$game->home_goals;
            } else {
                --$game->away_goals;
            }
        }

        $gameEvent->delete();
        $game->save();

        return response()->json(['message' => 'Gol eliminado correctamente.']);
    }
    public function getEvents(Game $game): JsonResponse
    {
        $game->load([
            'gameEvent' => function ($query) {
                $query->orderBy('minute', 'desc');
            },
            'gameEvent.player.user:id,name,last_name',
            'gameEvent.player.position:id,name',
            'gameEvent.team:id,name,colors,image',
            'gameEvent.relatedPlayer.user:id,name,last_name',
            'gameEvent.relatedPlayer.position:id,name',
        ]);

        return response()->json($game->gameEvent);
    }
}

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
use App\Models\Penalty;
use App\Models\Substitution;
use App\Support\MatchDuration;
use App\Traits\LineupTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameController extends Controller
{
    public const int TWO_HOURS = 120;
    public const int ONE_HOUR = 60;
    use LineupTrait;

    public function show(int $gameId): GameResource
    {
        $game = Game::with(["tournament.locations.fields", 'penalties.player.user'])->findOrFail($gameId);
        return new GameResource($game);
    }
    /*
     * Valida reprogramación sin solapamientos: además de caber en la ventana disponible,
     *  revisa que el intervalo propuesto no choque con ningún otro partido en el mismo campo y fecha (de cualquier torneo).
     *  Antes solo verificaba igualdad exacta de hora.
     * */
    public function update(Request $request, Game $game): GameResource
    {
        // Soporta dos modos: nuevo (starts_at ISO‑8601 con TZ) o legado (date + selected_time.start + day)
        $data = $request->validate([
            'starts_at' => 'nullable|date',
            'date' => 'nullable|date',
            'selected_time' => 'nullable|array',
            'selected_time.start' => 'nullable|date_format:H:i',
            'field_id' => 'required|exists:fields,id',
            'day' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        $leagueTz = $game->tournament->league->timezone ?? config('app.timezone', 'America/Mexico_City');
        if (!empty($data['starts_at'])) {
            // Entrada ISO-8601 (con o sin TZ). Si no trae TZ, se asume TZ de la liga
            $parsed = Carbon::parse($data['starts_at'], $leagueTz);
            $day = strtolower($parsed->format('l'));
            $newStart = $parsed->format('H:i');
            $date = $parsed->copy()->startOfDay();
        } else {
            // Modo legado
            $day = strtolower($data['day']);
            $newStart = $data['selected_time']['start'];
            $date = Carbon::parse($data['date'], $leagueTz)->startOfDay();
        }

        $config = $game->tournament->configuration;
        $matchDuration = MatchDuration::minutes($config);

        // ventanas efectivas para ese campo en esta liga excluyendo el torneo actual
        $leagueField = \App\Models\LeagueField::where('league_id', $game->tournament->league_id)
            ->where('field_id', $data['field_id'])
            ->firstOrFail();
        $weekly = app(\App\Services\AvailabilityService::class)
            ->getWeeklyWindowsForLeagueField($leagueField->id, $game->tournament_id);
        $map = ['sunday'=>0,'monday'=>1,'tuesday'=>2,'wednesday'=>3,'thursday'=>4,'friday'=>5,'saturday'=>6];
        $dow = $map[$day];
        $ranges = $weekly[$dow] ?? [];
        if (empty($ranges)) {
            abort(422, 'No hay disponibilidad para ese día.');
        }
        $startMinutes = intval(explode(':', $newStart)[0]) * 60 + intval(explode(':', $newStart)[1]);
        $endMinutes = $startMinutes + $matchDuration;
        $fits = false;
        foreach ($ranges as [$s,$e]) {
            if ($startMinutes >= $s && $endMinutes <= $e) { $fits = true; break; }
        }
        if (!$fits) {
            abort(422, 'La hora seleccionada no cabe en la disponibilidad.');
        }

        // evitar choque con otros juegos en el mismo campo/fecha (cualquier torneo)
        $conflicting = false;
        $others = Game::with(['tournament.configuration'])
            ->where('field_id', $data['field_id'])
            ->whereDate('match_date', $date)
            ->where('id', '!=', $game->id)
            ->get(['id', 'match_time', 'tournament_id']);
        foreach ($others as $og) {
            if (empty($og->match_time)) { continue; }
            $ogStart = \Carbon\Carbon::createFromFormat('H:i', $og->match_time);
            $ogCfg = $og->tournament->configuration;
            $ogDuration = MatchDuration::minutes($ogCfg, $matchDuration);
            $ogStartMin = $ogStart->hour * 60 + $ogStart->minute;
            $ogEndMin = $ogStartMin + $ogDuration;
            if ($startMinutes < $ogEndMin && $endMinutes > $ogStartMin) {
                $conflicting = true; break;
            }
        }
        if ($conflicting) {
            abort(422, 'Existe un partido que se solapa en ese campo y fecha.');
        }

        // Actualizar game
        // Calcular UTC
        $localStart = Carbon::parse($date->toDateString() . ' ' . $newStart, $leagueTz);
        $startsAtUtc = $localStart->clone()->setTimezone('UTC');
        $endsAtUtc = $startsAtUtc->clone()->addMinutes($matchDuration);

        $game->update([
            'match_date' => $date->toDateString(),
            'match_time' => $newStart . ':00',
            'starts_at_utc' => $startsAtUtc,
            'ends_at_utc' => $endsAtUtc,
            'field_id' => (int)$data['field_id'],
        ]);

        $game->loadMissing(['tournament.locations.fields', 'penalties.player.user']);

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

        if (!is_null($lineup)) {
            $lineup->setAttribute('team_color', $lineup->team->colors['home']['primary']);
            if ($lineup->lineupPlayers->isEmpty()) {
                $this->initDefaultLineupPlayers($lineup);
            }
            return $lineup;
        }

        $defaultLineup = DefaultLineup::where('team_id', $teamId)
            ->with(['defaultLineupPlayers','formation'])
            ->first();
        if (!is_null($defaultLineup)) {
            // create or update the lineup for the specific team in the specific game
            $lineup = Lineup::updateOrCreate([
                'game_id' => $game->id,
                'team_id' => $teamId,
            ],[
                'formation_id' => $defaultLineup->formation->id,
                'default_lineup_id' => $defaultLineup->id,
                'round' => $game->round,
            ]);
            $lineup->setAttribute('team_color', $lineup->team->colors['home']['primary']);
            $this->initDefaultLineupPlayers($lineup);
        }
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

    /**
     * Actualiza los eventos de gol de un partido e, opcionalmente, el detalle de penales de desempate.
     *
     * Flujo general:
     * 1. Validar la carga útil proveniente del acta (goles locales/visitantes y bloque de penales).
     * 2. Persistir/actualizar los GameEvent correspondientes al tiempo reglamentario (incluye penales marcados dentro del partido).
     * 3. Cuando el torneo permite definir empates en penales y el encuentro terminó igualado:
     *    a. Validar que exista información coherente de la tanda (tiradores, marcador y ganador).
     *    b. Guardar los intentos en la tabla penalties y reflejar el ganador de la tanda en el registro del juego.
     * 4. Cuando el empate no aplica a la regla (fase eliminatoria o marcador distinto), limpiar cualquier rastro previo de penales.
     * 5. Responder con un mensaje de éxito sin recalcular standings (eso ocurre al marcar el juego como completado).
     */
    public function goals(Request $request, Game $game): JsonResponse
    {
        $data = $request->validate([
            'home' => 'array',
            'away' => 'array',
            'shootout' => 'nullable|array',
        ]);

        $homeGoals = 0;
        $awayGoals = 0;

        $homeEvents = $data['home'] ?? [];
        $awayEvents = $data['away'] ?? [];

        foreach (['home' => $game->home_team_id, 'away' => $game->away_team_id] as $key => $teamId) {
            $events = $key === 'home' ? $homeEvents : $awayEvents;

            if (empty($events)) {
                continue;
            }

            foreach ($events as $goal) {
                if (!isset($goal['player_id'], $goal['minute'], $goal['type'])) {
                    continue;
                }

                if ($goal['type'] === GameEvent::OWN_GOAL) {
                    // Un autogol se almacena como evento del equipo que lo sufre, pero suma para el adversario.
                    GameEvent::updateOrCreate([
                        'game_id' => $game->id,
                        'type' => GameEvent::OWN_GOAL,
                        'minute' => $goal['minute'],
                        'player_id' => $goal['player_id'],
                        'team_id' => $teamId,
                    ], [
                        'related_player_id' => null,
                    ]);

                    if ($key === 'home') {
                        ++$awayGoals;
                    } else {
                        ++$homeGoals;
                    }
                } elseif (in_array($goal['type'], [GameEvent::GOAL, GameEvent::PENALTY], true)) {
                    GameEvent::updateOrCreate([
                        'game_id' => $game->id,
                        'type' => $goal['type'],
                        'minute' => $goal['minute'],
                        'player_id' => $goal['player_id'],
                        'team_id' => $teamId,
                    ], [
                        'related_player_id' => $goal['related_player_id'] ?? null,
                    ]);

                    if ($key === 'home') {
                        ++$homeGoals;
                    } else {
                        ++$awayGoals;
                    }
                }
            }
        }

        $shootout = $data['shootout'] ?? null;
        $applyShootoutRule = $game->tournament->penalty_draw_enabled && !$this->isEliminationPhaseGame($game);

        if ($applyShootoutRule && data_get($shootout, 'decided')) {
            // Solo aceptamos la tanda si el marcador regular finalizó empatado.
            if ($homeGoals !== $awayGoals) {
                throw ValidationException::withMessages([
                    'shootout' => ['Solo se permite desempate por penales cuando el marcador quedó empatado.'],
                ]);
            }

            // Normalizamos y validamos el bloque de penales local.
            $homeAttempts = collect(data_get($shootout, 'home', []))
                ->map(function ($attempt, $index) use ($game) {
                    if (!isset($attempt['player_id'])) {
                        throw ValidationException::withMessages([
                            'shootout.home.' . $index . '.player_id' => ['El jugador es obligatorio.'],
                        ]);
                    }

                    return [
                        'player_id' => (int) $attempt['player_id'],
                        'team_id' => $game->home_team_id,
                        'score_goal' => filter_var($attempt['score_goal'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'kicks_number' => (int) ($attempt['kicks_number'] ?? ($index + 1)),
                    ];
                });

            $awayAttempts = collect(data_get($shootout, 'away', []))
                ->map(function ($attempt, $index) use ($game) {
                    if (!isset($attempt['player_id'])) {
                        throw ValidationException::withMessages([
                            'shootout.away.' . $index . '.player_id' => ['El jugador es obligatorio.'],
                        ]);
                    }

                    return [
                        'player_id' => (int) $attempt['player_id'],
                        'team_id' => $game->away_team_id,
                        'score_goal' => filter_var($attempt['score_goal'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'kicks_number' => (int) ($attempt['kicks_number'] ?? ($index + 1)),
                    ];
                });

            if ($homeAttempts->isEmpty() || $awayAttempts->isEmpty()) {
                throw ValidationException::withMessages([
                    'shootout' => ['Registra al menos un cobro de penal por equipo.'],
                ]);
            }

            $homeShootoutGoals = $homeAttempts->where('score_goal', true)->count();
            $awayShootoutGoals = $awayAttempts->where('score_goal', true)->count();

            if ($homeShootoutGoals === $awayShootoutGoals) {
                throw ValidationException::withMessages([
                    'shootout' => ['El desempate por penales debe tener un ganador.'],
                ]);
            }

            $winnerTeamId = $homeShootoutGoals > $awayShootoutGoals ? $game->home_team_id : $game->away_team_id;

            DB::transaction(function () use ($game, $homeAttempts, $awayAttempts) {
                // Limpiamos intentos previos antes de reemplazarlos por la nueva tanda reportada.
                Penalty::where('game_id', $game->id)->forceDelete();

                $homeAttempts->each(function ($attempt) use ($game) {
                    Penalty::create(array_merge($attempt, ['game_id' => $game->id]));
                });

                $awayAttempts->each(function ($attempt) use ($game) {
                    Penalty::create(array_merge($attempt, ['game_id' => $game->id]));
                });
            });

            $game->decided_by_penalties = true;
            $game->penalty_home_goals = $homeShootoutGoals;
            $game->penalty_away_goals = $awayShootoutGoals;
            $game->penalty_winner_team_id = $winnerTeamId;
        } else {
            // Si no aplica la regla, aseguramos que el juego no conserve datos residuales de tandas anteriores.
            Penalty::where('game_id', $game->id)->forceDelete();
            $game->decided_by_penalties = false;
            $game->penalty_home_goals = null;
            $game->penalty_away_goals = null;
            $game->penalty_winner_team_id = null;
        }

        $game->home_goals = $homeGoals;
        $game->away_goals = $awayGoals;
        $game->save();

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
        $goalEvents = $game->gameEvent()
            ->whereIn('type', [GameEvent::GOAL, GameEvent::PENALTY, GameEvent::OWN_GOAL])
            ->orderBy('minute')
            ->get();
        $homeTeamId = $game->home_team_id;
        $awayTeam = $game->awayTeam->only(['id','name']);

        $goalCountByMinute = [];

        $homeGoals = 0;
        $awayGoals = 0;

        foreach ($goalEvents as $event) {
            $minute = $event->minute;
            $type = $event->type;
            $teamId = $event->team_id;

            $isOwnGoal = $type === GameEvent::OWN_GOAL;

            // Increment goal for the correct team
            if ($isOwnGoal) {
                if ($teamId === $homeTeamId) {
                    $awayGoals++;
                } else {
                    $homeGoals++;
                }
            } elseif ($teamId === $homeTeamId) {
                $homeGoals++;
            } else {
                $awayGoals++;
            }

            // Save the score snapshot at this minute
            $goalCountByMinute[$minute] = [
                'home' => $homeGoals,
                'away' => $awayGoals,
            ];
        }
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
        $game->gameEvent->each(function ($event) use ($goalCountByMinute, $awayTeam) {
            if (in_array($event->type, [GameEvent::GOAL, GameEvent::PENALTY, GameEvent::OWN_GOAL])) {
                $event->setAttribute('away_team', $awayTeam);
                $minute = $event->minute;
                // Find the most recent minute ≤ current event
                $availableMinutes = array_filter(array_keys($goalCountByMinute), fn($m) => $m <= $minute);
                $closestMinute = !empty($availableMinutes) ? max($availableMinutes) : null;

                if ($closestMinute !== null) {
                    $snapshot = $goalCountByMinute[$closestMinute];
                    // Assign both home and away goals to the event
                    $event->setAttribute('home_goals_at', $snapshot['home']);
                    $event->setAttribute('away_goals_at', $snapshot['away']);
                } else {
                    $event->setAttribute('home_goals_at', 0);
                    $event->setAttribute('away_goals_at', 0);
                }
            }
        });



        return response()->json($game->gameEvent);
    }

    /**
     * Determina si el juego pertenece a una fase eliminatoria para evitar aplicar la regla de 2/1 puntos.
     */
    private function isEliminationPhaseGame(Game $game): bool
    {
        $phaseName = optional($game->tournamentPhase?->phase)->name;

        if (!$phaseName) {
            return false;
        }

        return in_array($phaseName, [
            'Dieciseisavos de Final',
            'Octavos de Final',
            'Cuartos de Final',
            'Semifinales',
            'Final',
        ], true);
    }
    /**
     * Marca el juego como completado; el GameObserver desencadena el recalculo de standings.
     */
    public function markAsComplete(Game $game): JsonResponse
    {
        $game->update(['status' => Game::STATUS_COMPLETED]);

        return response()->json(['message' => 'Partido actualizado correctamente.']);
    }
}

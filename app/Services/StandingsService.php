<?php

namespace App\Services;

use App\Models\Phase;
use App\Models\Standing;
use App\Models\TournamentPhase;
use Illuminate\Support\Facades\DB;

class StandingsService
{
    /**
     * Recalcula la tabla de posiciones para una fase específica de un torneo.
     *
     * Flujo de alto nivel:
     * - Determinar si el torneo está configurado con empates por penales y, cuando sea necesario,
     *   crear/recuperar la fase "Tabla general" para juegos sin tournament_phase_id.
     * - Iterar todos los partidos completados (orden cronológico) acumulando estadísticas en memoria.
     * - Para empates con penales habilitados: asignar 2 puntos al ganador de la tanda y 1 al perdedor.
     * - Persistir los acumulados en standings y recalcular los rankings aplicando los desempates configurados.
     *
     * @param int $tournamentId
     * @param int|null $tournamentPhaseId Fase objetivo; null representa la fase general.
     * @param int|null $triggeringGameId Game que detonó el proceso (se usa para updated_after_game_id).
     * @throws Throwable
     */
    public function recalculateStandingsForPhase(int $tournamentId, ?int $tournamentPhaseId = null, ?int $triggeringGameId = null): void
    {
        DB::transaction(function () use ($tournamentId, $tournamentPhaseId, $triggeringGameId) {
            $penaltyDrawEnabled = (bool) DB::table('tournaments')
                ->where('id', $tournamentId)
                ->value('penalty_draw_enabled');

            $standingsPhaseId = $this->resolveStandingsPhaseId($tournamentId, $tournamentPhaseId);

            $phaseNameMap = DB::table('tournament_phases')
                ->join('phases', 'phases.id', '=', 'tournament_phases.phase_id')
                ->where('tournament_phases.tournament_id', $tournamentId)
                ->pluck('phases.name', 'tournament_phases.id')
                ->toArray();

            $eliminationPhaseNames = [
                'Dieciseisavos de Final',
                'Octavos de Final',
                'Cuartos de Final',
                'Semifinales',
                'Final',
            ];

            // 1) Mapear team_tournament (team_id => team_tournament_id)
            $teamTournamentRows = DB::table('team_tournament')
                ->where('tournament_id', $tournamentId)
                ->get(['id','team_id','group_key']);
            $teamTournamentMap = $teamTournamentRows->pluck('id','team_id')->toArray();
            $teamGroupMap = $teamTournamentRows->pluck('group_key','team_id')->toArray();

            // 2) Inicializar estructura in-memory para stats
            $stats = [];
            foreach ($teamTournamentMap as $teamId => $teamTournamentId) {
                $stats[$teamId] = [
                    'team_id' => $teamId,
                    'team_tournament_id' => $teamTournamentId,
                    'matches_played' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'points' => 0,
                    'results' => [], // array de 'W','D','L' en orden cronológico
                ];
            }

            // 3) Obtener partidos completados de la fase, orden cronológico (fecha,hora,id)
            $gamesQuery = DB::table('games')
                ->where('tournament_id', $tournamentId)
                ->where('status', 'completado');

            if (is_null($tournamentPhaseId)) {
                $gamesQuery->whereNull('tournament_phase_id');
            } else {
                $gamesQuery->where('tournament_phase_id', $tournamentPhaseId);
            }

            $games = $gamesQuery
                ->orderBy('match_date')
                ->orderBy('match_time')
                ->orderBy('id')
                ->get();

            // Detectar si es fase de grupos
            $isGroupPhase = false;
            if (!is_null($tournamentPhaseId)) {
                $phaseName = $phaseNameMap[$tournamentPhaseId] ?? null;
                $isGroupPhase = ($phaseName === 'Fase de grupos');
            }

            // 4) Procesar cada juego (cronológico) y actualizar estructuras
            foreach ($games as $g) {
                $home = (int) $g->home_team_id;
                $away = (int) $g->away_team_id;
                $hg = (int) $g->home_goals;
                $ag = (int) $g->away_goals;
                $gamePhaseName = $phaseNameMap[$g->tournament_phase_id] ?? null;
                $isEliminationGame = in_array($gamePhaseName, $eliminationPhaseNames, true);
                $applyPenaltyRuleForGame = $penaltyDrawEnabled && !$isEliminationGame;

                // En fase de grupos: contar solo partidos entre equipos del mismo grupo
                if ($isGroupPhase) {
                    $gkH = $teamGroupMap[$home] ?? null;
                    $gkA = $teamGroupMap[$away] ?? null;
                    if (empty($gkH) || empty($gkA) || $gkH !== $gkA) {
                        continue; // ignorar partidos cruzados
                    }
                }

                // Asegurarnos que el equipo exista en stats (por si no tenía team_tournament)
                foreach ([$home, $away] as $teamId) {
                    if (!isset($stats[$teamId])) {
                        $teamTournamentId = DB::table('team_tournament')
                            ->where('tournament_id', $tournamentId)
                            ->where('team_id', $teamId)
                            ->value('id');

                        $stats[$teamId] = [
                            'team_id' => $teamId,
                            'team_tournament_id' => $teamTournamentId,
                            'matches_played' => 0,
                            'wins' => 0,
                            'draws' => 0,
                            'losses' => 0,
                            'goals_for' => 0,
                            'goals_against' => 0,
                            'points' => 0,
                            'results' => [],
                        ];
                    }
                }

                // Home update
                $stats[$home]['matches_played']++;
                $stats[$home]['goals_for'] += $hg;
                $stats[$home]['goals_against'] += $ag;

                // Away update
                $stats[$away]['matches_played']++;
                $stats[$away]['goals_for'] += $ag;
                $stats[$away]['goals_against'] += $hg;

                // Resultados y puntos
                if ($hg > $ag) {
                    $stats = $this->updateStats($stats, $home, $away);
                } elseif ($hg === $ag) {
                    if (
                        $applyPenaltyRuleForGame
                        && (bool) $g->decided_by_penalties
                        && in_array((int) $g->penalty_winner_team_id, [$home, $away], true)
                    ) {
                        $winner = (int) $g->penalty_winner_team_id;
                        $loser = $winner === $home ? $away : $home;

                        $stats[$winner]['wins']++;
                        $stats[$winner]['points'] += 2;
                        $stats[$winner]['results'][] = 'W';

                        $stats[$loser]['losses']++;
                        $stats[$loser]['points'] += 1;
                        $stats[$loser]['results'][] = 'L';
                    } else {
                        $stats[$home]['draws']++;
                        ++$stats[$home]['points'];
                        $stats[$home]['results'][] = 'D';

                        $stats[$away]['draws']++;
                        ++$stats[$away]['points'];
                        $stats[$away]['results'][] = 'D';
                    }
                } else {
                    $stats = $this->updateStats($stats, $away, $home);
                }
            }

            // 5) Calcular GD, last_5 y persistir (updateOrCreate)
            // Obtener league_id del torneo (si/cuando lo necesites)
            $leagueId = DB::table('tournaments')->where('id', $tournamentId)->value('league_id');

            foreach ($stats as $teamId => $s) {
                $gd = $s['goals_for'] - $s['goals_against'];

                // Construir last_5 en el mismo formato: 5 caracteres, '-' para espacios vacíos,
                // orden: más antiguo a la izquierda, último (más reciente) a la derecha.
                $results = $s['results'];
                $last5Arr = array_slice($results, -5);
                $pad = max(0, 5 - count($last5Arr));
                $last5Str = str_repeat('-', $pad) . implode('', $last5Arr);

                // upsert
                Standing::updateOrCreate(
                    [
                        'team_id' => $teamId,
                        'team_tournament_id' => $s['team_tournament_id'],
                        'tournament_phase_id' => $standingsPhaseId,
                    ],
                    [
                        'matches_played' => $s['matches_played'],
                        'wins' => $s['wins'],
                        'draws' => $s['draws'],
                        'losses' => $s['losses'],
                        'goals_for' => $s['goals_for'],
                        'goals_against' => $s['goals_against'],
                        'goal_difference' => $gd,
                        'points' => $s['points'],
                        'last_5' => $last5Str,
                        'tournament_id' => $tournamentId,
                        'league_id' => $leagueId,
                        'updated_after_game_id' => $triggeringGameId,
                    ]
                );
            }

            // 6) Recalcular ranks (standard competition ranking)
            $this->recalculateRanks($tournamentId, $standingsPhaseId, $tournamentPhaseId);
        });
    }

    /**
     * Recalcula y persiste el campo `rank` para una fase determinada,
     * respetando los criterios configurados en tournament_tiebreakers.
     */
    protected function recalculateRanks(int $tournamentId, int $standingsPhaseId, ?int $phaseFilter = null): void
    {
        $tiebreakers = DB::table('tournament_tiebreakers')
            ->join('tournament_configurations', 'tournament_configurations.id', '=', 'tournament_tiebreakers.tournament_configuration_id')
            ->where('tournament_configurations.tournament_id', $tournamentId)
            ->where('tournament_tiebreakers.is_active', 1)
            ->orderBy('tournament_tiebreakers.priority')
            ->pluck('tournament_tiebreakers.rule')
            ->toArray();

        $orderMap = [
            'Puntos'                  => ['points', 'desc'],
            'Diferencia de goles'     => ['goal_difference', 'desc'],
            'Goles a favor'           => ['goals_for', 'desc'],
            'Goles en contra'         => ['goals_against', 'asc'],
            'Resultado entre equipos' => 'head_to_head',
            'Sorteo'                  => null,
        ];

        // Query base
        $query = Standing::where('tournament_id', $tournamentId)
            ->where('tournament_phase_id', $standingsPhaseId);

        // Aplicar solo reglas directas (antes de head-to-head)
        foreach ($tiebreakers as $rule) {
            if (!isset($orderMap[$rule])) continue;
            if ($orderMap[$rule] === null || $orderMap[$rule] === 'head_to_head') continue;
            [$col, $dir] = $orderMap[$rule];
            $query->orderBy($col, $dir);
        }

        $rows = $query->get();

        // Detectar bloques empatados
        $rank = 1;
        $i = 0;
        while ($i < $rows->count()) {
            $current = $rows[$i];

            // Construir key según reglas antes de head_to_head
            $key = [];
            foreach ($tiebreakers as $rule) {
                if (!isset($orderMap[$rule])) continue;
                if ($orderMap[$rule] === null || $orderMap[$rule] === 'head_to_head') continue;
                $col = $orderMap[$rule][0];
                $key[] = $current->{$col};
            }
            $key = implode('|', $key);

            // Agrupar empatados
            $block = [$current];
            $j = $i + 1;
            while ($j < $rows->count()) {
                $next = $rows[$j];
                $nextKey = [];
                foreach ($tiebreakers as $rule) {
                    if (!isset($orderMap[$rule])) continue;
                    if ($orderMap[$rule] === null || $orderMap[$rule] === 'head_to_head') continue;
                    $col = $orderMap[$rule][0];
                    $nextKey[] = $next->{$col};
                }
                $nextKey = implode('|', $nextKey);
                if ($nextKey === $key) {
                    $block[] = $next;
                    $j++;
                } else {
                    break;
                }
            }

            // Si hay empate y "Resultado entre equipos" está activo
            if (count($block) > 1 && in_array('Resultado entre equipos', $tiebreakers)) {
                $block = $this->applyHeadToHead($block, $tournamentId, $phaseFilter);
            }

            // Asignar rank secuencial (sin repeticiones)
            foreach ($block as $b) {
                $b->rank = $rank++;
                $b->saveQuietly();
            }

            $i = $j;
        }
    }
    /**
     * Construye una mini tabla entre equipos empatados cuando el criterio "Resultado entre equipos" está activo.
     */
    protected function applyHeadToHead(array $block, int $tournamentId, ?int $phaseFilter): array
    {
        $teamIds = array_map(fn($s) => $s->team_id, $block);

        // Obtener partidos solo entre estos equipos
        $games = DB::table('games')
            ->where('tournament_id', $tournamentId)
            ->when($phaseFilter, fn($q) => $q->where('tournament_phase_id', $phaseFilter))
            ->when(is_null($phaseFilter), fn($q) => $q->whereNull('tournament_phase_id'))
            ->where('status', 'completado')
            ->whereIn('home_team_id', $teamIds)
            ->whereIn('away_team_id', $teamIds)
            ->get();

        // Construir mini tabla
        $mini = [];
        foreach ($teamIds as $id) {
            $mini[$id] = ['points' => 0, 'goal_difference' => 0, 'goals_for' => 0, 'goals_against' => 0];
        }

        foreach ($games as $g) {
            $hg = (int)$g->home_goals;
            $ag = (int)$g->away_goals;

            // Stats para local
            $mini[$g->home_team_id]['goals_for'] += $hg;
            $mini[$g->home_team_id]['goals_against'] += $ag;
            $mini[$g->home_team_id]['goal_difference'] = $mini[$g->home_team_id]['goals_for'] - $mini[$g->home_team_id]['goals_against'];

            // Stats para visitante
            $mini[$g->away_team_id]['goals_for'] += $ag;
            $mini[$g->away_team_id]['goals_against'] += $hg;
            $mini[$g->away_team_id]['goal_difference'] = $mini[$g->away_team_id]['goals_for'] - $mini[$g->away_team_id]['goals_against'];

            // Asignar puntos
            if ($hg > $ag) {
                $mini[$g->home_team_id]['points'] += 3;
            } elseif ($hg < $ag) {
                $mini[$g->away_team_id]['points'] += 3;
            } else {
                if ((bool) $g->decided_by_penalties && in_array((int)$g->penalty_winner_team_id, [$g->home_team_id, $g->away_team_id], true)) {
                    $winnerId = (int) $g->penalty_winner_team_id;
                    $loserId = $winnerId === (int)$g->home_team_id
                        ? (int)$g->away_team_id
                        : (int)$g->home_team_id;

                    $mini[$winnerId]['points'] += 2;
                    $mini[$loserId]['points'] += 1;
                } else {
                    ++$mini[$g->home_team_id]['points'];
                    ++$mini[$g->away_team_id]['points'];
                }
            }
        }

        // Ordenar bloque usando mini tabla
        usort($block, function ($a, $b) use ($mini) {
            return
                ($mini[$b->team_id]['points'] <=> $mini[$a->team_id]['points']) ?:
                    ($mini[$b->team_id]['goal_difference'] <=> $mini[$a->team_id]['goal_difference']) ?:
                        ($mini[$b->team_id]['goals_for'] <=> $mini[$a->team_id]['goals_for']);
        });

        return $block;
    }

    /**
     * @param array $stats
     * @param int $away
     * @param int $home
     * @return array
     */
    /**
     * Aplica el resultado de un partido con ganador perdedor (90 minutos) al acumulado de standings.
     */
    public function updateStats(array $stats, int $away, int $home): array
    {
        $stats[$away]['wins']++;
        $stats[$away]['points'] += 3;
        $stats[$away]['results'][] = 'W';

        $stats[$home]['losses']++;
        $stats[$home]['results'][] = 'L';
        return $stats;
    }

    /**
     * Obtiene (o crea) el identificador de fase que se usará en standings.
     * - Si el partido pertenece a una fase concreta, reutilizamos ese ID.
     * - Si no tiene fase (tabla general), garantizamos que exista un tournament_phase "Tabla general".
     */
    protected function resolveStandingsPhaseId(int $tournamentId, ?int $tournamentPhaseId): int
    {
        if (!is_null($tournamentPhaseId)) {
            return $tournamentPhaseId;
        }

        $existing = DB::table('tournament_phases')
            ->join('phases', 'phases.id', '=', 'tournament_phases.phase_id')
            ->where('tournament_phases.tournament_id', $tournamentId)
            ->where('phases.name', 'Tabla general')
            ->value('tournament_phases.id');

        if ($existing) {
            return (int) $existing;
        }

        $phase = Phase::firstOrCreate(
            ['name' => 'Tabla general'],
            ['is_active' => true, 'is_completed' => false, 'min_teams_for' => null]
        );

        $tournamentPhase = TournamentPhase::create([
            'phase_id' => $phase->id,
            'tournament_id' => $tournamentId,
            'is_active' => true,
            'is_completed' => false,
        ]);

        return $tournamentPhase->id;
    }

}
